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

/**
 * \file    whatsapp/class/whatsappwebhookauth.class.php
 * \ingroup whatsapp
 * \brief   Authentication helpers for the public webhook receiver.
 *
 * Pure PHP on purpose: no Dolibarr globals and no database, so it can be unit
 * tested without booting Dolibarr. The receiver passes in the request parts.
 */

/**
 * Class WhatsappWebhookAuth
 */
class WhatsappWebhookAuth
{
	/** Header name used to carry the shared secret. */
	const HEADER_NAME = 'X-Webhook-Secret';

	/** Requests bigger than this are rejected before JSON decoding (1 MiB). */
	const MAX_BODY_BYTES = 1048576;

	/**
	 * Read an HTTP header from $_SERVER.
	 *
	 * @param	array	$server	$_SERVER
	 * @param	string	$name	Header name, e.g. 'X-Webhook-Secret'
	 * @return	string			Trimmed value or '' when absent
	 */
	public static function getHeader($server, $name)
	{
		$key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
		if (isset($server[$key]) && is_string($server[$key])) {
			return trim($server[$key]);
		}
		// Some servers strip Authorization from HTTP_* and keep it in these.
		if (strcasecmp($name, 'Authorization') === 0) {
			foreach (array('REDIRECT_HTTP_AUTHORIZATION', 'PHP_AUTH_DIGEST') as $alt) {
				if (isset($server[$alt]) && is_string($server[$alt])) {
					return trim($server[$alt]);
				}
			}
		}
		return '';
	}

	/**
	 * Extract the secret presented by the caller.
	 *
	 * Order: X-Webhook-Secret header, Authorization: Bearer, JSON body "apikey",
	 * query string "secret". The body field is what Evolution API sends, so it
	 * keeps working without reconfiguring the gateway.
	 *
	 * @param	array	$eventData	Decoded JSON body
	 * @param	array	$server		$_SERVER
	 * @param	array	$query		$_GET / query string
	 * @return	string				Presented secret or ''
	 */
	public static function getProvidedSecret($eventData, $server, $query = array())
	{
		$header = self::getHeader($server, self::HEADER_NAME);
		if ($header !== '') {
			return $header;
		}

		$auth = self::getHeader($server, 'Authorization');
		if ($auth !== '' && stripos($auth, 'Bearer ') === 0) {
			$token = trim(substr($auth, 7));
			if ($token !== '') {
				return $token;
			}
		}

		if (is_array($eventData) && isset($eventData['apikey']) && is_string($eventData['apikey'])) {
			$apikey = trim($eventData['apikey']);
			if ($apikey !== '') {
				return $apikey;
			}
		}

		if (is_array($query) && isset($query['secret']) && is_string($query['secret'])) {
			$secret = trim($query['secret']);
			if ($secret !== '') {
				return $secret;
			}
		}

		return '';
	}

	/**
	 * Every secret the caller presents, in the same order as getProvidedSecret().
	 *
	 * getProvidedSecret() only returns the first one, so a non-matching early
	 * candidate shadows a correct later one: Evolution API puts the instance
	 * token in the body "apikey" of every event, which hid a correct "?secret="
	 * in the URL and the receiver answered 401. Checking all of them only adds
	 * accepted cases; everything accepted before is still accepted.
	 *
	 * @param	array	$eventData	Decoded JSON body
	 * @param	array	$server		$_SERVER
	 * @param	array	$query		$_GET / query string
	 * @return	string[]			Non-empty candidates, without duplicates
	 */
	public static function getProvidedSecrets($eventData, $server, $query = array())
	{
		$candidates = array();

		$candidates[] = self::getHeader($server, self::HEADER_NAME);

		$auth = self::getHeader($server, 'Authorization');
		if ($auth !== '' && stripos($auth, 'Bearer ') === 0) {
			$candidates[] = trim(substr($auth, 7));
		}

		if (is_array($eventData) && isset($eventData['apikey']) && is_string($eventData['apikey'])) {
			$candidates[] = trim($eventData['apikey']);
		}

		if (is_array($query) && isset($query['secret']) && is_string($query['secret'])) {
			$candidates[] = trim($query['secret']);
		}

		$result = array();
		foreach ($candidates as $candidate) {
			if ($candidate !== '' && !in_array($candidate, $result, true)) {
				$result[] = $candidate;
			}
		}
		return $result;
	}

	/**
	 * Whether any of the presented secrets matches any configured one.
	 *
	 * Every pair is compared with hash_equals() and the loop never stops early,
	 * so the time spent does not depend on which candidate matched.
	 *
	 * @param	string[]	$providedList	Secrets presented by the caller
	 * @param	array		$allowed		Configured secrets (empty entries ignored)
	 * @return	bool
	 */
	public static function matchesAny($providedList, $allowed)
	{
		$ok = false;
		foreach ((array) $providedList as $provided) {
			if (self::matches($provided, $allowed)) {
				$ok = true;
			}
		}
		return $ok;
	}

	/**
	 * Compare the presented secret against the configured ones.
	 *
	 * hash_equals() is used for every candidate so the comparison is constant
	 * time and does not leak which one matched.
	 *
	 * @param	string	$provided	Secret sent by the caller
	 * @param	array	$allowed	Configured secrets (empty entries ignored)
	 * @return	bool
	 */
	public static function matches($provided, $allowed)
	{
		if (!is_string($provided) || $provided === '') {
			return false;
		}
		foreach ((array) $allowed as $candidate) {
			if (is_string($candidate) && $candidate !== '' && hash_equals($candidate, $provided)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether the raw body exceeds the accepted size.
	 *
	 * @param	int		$length	Byte length of the raw body
	 * @return	bool
	 */
	public static function isBodyTooLarge($length)
	{
		return ((int) $length) > self::MAX_BODY_BYTES;
	}

	/**
	 * Mask a secret so it is never persisted in clear text by mistake.
	 *
	 * @param	string	$value	Value stored by the caller (usually the apikey field)
	 * @param	array	$allowed	Configured secrets
	 * @return	string			Empty when the value is a configured secret
	 */
	public static function maskIfSecret($value, $allowed)
	{
		if (self::matches($value, $allowed)) {
			return '';
		}
		return (string) $value;
	}
}
