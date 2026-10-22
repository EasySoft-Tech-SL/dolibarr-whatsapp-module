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

	public function testSecretIsMaskedBeforePersisting()
	{
		$this->assertSame('', WhatsappWebhookAuth::maskIfSecret('s3cret', array('s3cret')));
		$this->assertSame('other-key', WhatsappWebhookAuth::maskIfSecret('other-key', array('s3cret')));
	}
}
