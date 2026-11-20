<?php
/* Copyright (C) 2026 Alberto Luque Rivas <aluquerivasdev@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/class/whatsappwebhookauth.class.php';

/**
 * Unit tests for the webhook authentication helpers.
 *
 * These run without Dolibarr: the class is pure PHP on purpose.
 */
final class WhatsappWebhookAuthTest extends TestCase
{
	public function testHeaderIsPreferredOverBody()
	{
		$server = array('HTTP_X_WEBHOOK_SECRET' => ' from-header ');
		$body = array('apikey' => 'from-body');

		$this->assertSame('from-header', WhatsappWebhookAuth::getProvidedSecret($body, $server, array()));
	}

	public function testAuthorizationBearerIsAccepted()
	{
		$server = array('HTTP_AUTHORIZATION' => 'Bearer abc123');
		$this->assertSame('abc123', WhatsappWebhookAuth::getProvidedSecret(array(), $server, array()));
	}

	public function testBodyApikeyIsAccepted()
	{
		$body = array('apikey' => 'evolution-key');
		$this->assertSame('evolution-key', WhatsappWebhookAuth::getProvidedSecret($body, array(), array()));
	}

	public function testQuerySecretIsAccepted()
	{
		$query = array('secret' => 'query-secret');
		$this->assertSame('query-secret', WhatsappWebhookAuth::getProvidedSecret(array(), array(), $query));
	}

	public function testMissingSecretReturnsEmptyString()
	{
		$this->assertSame('', WhatsappWebhookAuth::getProvidedSecret(array(), array(), array()));
	}

	public function testNonStringApikeyIsIgnored()
	{
		$body = array('apikey' => array('nested'));
		$this->assertSame('', WhatsappWebhookAuth::getProvidedSecret($body, array(), array()));
	}

	public function testMatchesConfiguredSecret()
	{
		$this->assertTrue(WhatsappWebhookAuth::matches('s3cret', array('s3cret')));
	}

	public function testMatchesFallbackCandidate()
	{
		$this->assertTrue(WhatsappWebhookAuth::matches('server-token', array('dedicated-secret', 'server-token')));
	}

	public function testRejectsWrongSecret()
	{
		$this->assertFalse(WhatsappWebhookAuth::matches('wrong', array('s3cret')));
	}

	public function testRejectsDifferentLengthSecret()
	{
		$this->assertFalse(WhatsappWebhookAuth::matches('s3cret-longer', array('s3cret')));
	}

	public function testRejectsEmptyProvidedSecret()
	{
		$this->assertFalse(WhatsappWebhookAuth::matches('', array('s3cret')));
	}

	public function testRejectsEmptyConfiguration()
	{
		$this->assertFalse(WhatsappWebhookAuth::matches('s3cret', array('', null)));
	}

	public function testBodySizeLimit()
	{
		$this->assertFalse(WhatsappWebhookAuth::isBodyTooLarge(WhatsappWebhookAuth::MAX_BODY_BYTES));
		$this->assertTrue(WhatsappWebhookAuth::isBodyTooLarge(WhatsappWebhookAuth::MAX_BODY_BYTES + 1));
	}

	public function testAllPresentedSecretsAreCollectedInOrder()
	{
		$server = array('HTTP_X_WEBHOOK_SECRET' => 'h', 'HTTP_AUTHORIZATION' => 'Bearer b');
		$body = array('apikey' => 'token-instancia');
		$query = array('secret' => 'q');
		$this->assertSame(array('h', 'b', 'token-instancia', 'q'), WhatsappWebhookAuth::getProvidedSecrets($body, $server, $query));
	}

	public function testEmptyAndDuplicatedCandidatesAreDropped()
	{
		$body = array('apikey' => '  ');
		$query = array('secret' => 'same');
		$server = array('HTTP_X_WEBHOOK_SECRET' => 'same');
		$this->assertSame(array('same'), WhatsappWebhookAuth::getProvidedSecrets($body, $server, $query));
		$this->assertSame(array(), WhatsappWebhookAuth::getProvidedSecrets(array(), array(), array()));
	}

	public function testBodyApikeyNoLongerShadowsTheUrlSecret()
	{
		// Evolution manda siempre el token de la instancia en "apikey": con solo el primer candidato,
		// un ?secret= correcto en la URL quedaba anulado y el receptor respondía 401.
		$candidates = WhatsappWebhookAuth::getProvidedSecrets(array('apikey' => 'token-instancia'), array(), array('secret' => 'mi-secreto'));
		$this->assertFalse(WhatsappWebhookAuth::matches(WhatsappWebhookAuth::getProvidedSecret(array('apikey' => 'token-instancia'), array(), array('secret' => 'mi-secreto')), array('mi-secreto')));
		$this->assertTrue(WhatsappWebhookAuth::matchesAny($candidates, array('mi-secreto')));
	}

	public function testEverythingAcceptedBeforeIsStillAccepted()
	{
		// Token de la instancia configurado como secreto y un ?secret= viejo en la URL: sigue valiendo
		$candidates = WhatsappWebhookAuth::getProvidedSecrets(array('apikey' => 'token-instancia'), array(), array('secret' => 'viejo'));
		$this->assertTrue(WhatsappWebhookAuth::matchesAny($candidates, array('token-instancia')));
	}

	public function testMatchesAnyRejectsWhenNothingMatches()
	{
		$this->assertFalse(WhatsappWebhookAuth::matchesAny(array('a', 'b'), array('s3cret')));
		$this->assertFalse(WhatsappWebhookAuth::matchesAny(array(), array('s3cret')));
		$this->assertFalse(WhatsappWebhookAuth::matchesAny(array('a'), array('', null)));
	}

	public function testReceiverChecksEveryPresentedSecret()
	{
		$receiver = file_get_contents(dirname(__DIR__, 2) . '/public/webhook/receiver.php');
		$this->assertStringContainsString('WhatsappWebhookAuth::getProvidedSecrets(', $receiver);
		$this->assertStringContainsString('WhatsappWebhookAuth::matchesAny(', $receiver);
	}

	public function testSecretIsMaskedBeforePersisting()
	{
		$this->assertSame('', WhatsappWebhookAuth::maskIfSecret('s3cret', array('s3cret')));
		$this->assertSame('other-key', WhatsappWebhookAuth::maskIfSecret('other-key', array('s3cret')));
	}
}
