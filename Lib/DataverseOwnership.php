<?php
/**
 * COmanage Registry Dataverse Provisioner Account Ownership Rules
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This class holds the decisions about which Dataverse account belongs to a
 * CO Person. It has no CakePHP or Registry dependencies so that it can be
 * unit tested with plain PHPUnit (see Test/Case/Lib). The model gathers the
 * facts (the CO Person's attributes and candidate Dataverse accounts) and
 * acts on the decisions returned here.
 *
 * A "person" array has the keys providerId, persistentUserId, email,
 * emailVerified, and username. An "account" array is a Dataverse
 * authenticated user as returned by GET /api/admin/authenticatedUsers/{id}.
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class DataverseOwnership {
  // Outcomes of classify()
  const OUTCOME_OWNED = 'owned';
  const OUTCOME_CONFLICT = 'conflict';
  const OUTCOME_CREATE = 'create';

  // Rules by which an account was shown to belong to a CO Person
  const RULE_LOGIN = 'login';
  const RULE_EMAIL = 'email';

  // Link states parsed from the ProvisioningTarget Identifier value
  const LINK_NONE = 'none';
  const LINK_PREFIX = 'prefix';
  const LINK_TRUSTED = 'trusted';

  // Suffix marking a link made under the ownership rules
  const LINK_VERSION = 'v2';

  /**
   * Decide which Dataverse account, if any, belongs to a CO Person.
   *
   * The login identity rule is tried over all candidates before the email
   * rule. An account at the CO Person's username that is not theirs is a
   * conflict when no other candidate belongs to them.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array $person          CO Person facts
   * @param  Array $usernameAccount Account at the CO Person's username, or null if the username is free
   * @param  Array $emailAccounts   Accounts whose email matched the CO Person's email
   * @return Array outcome, account, rule, deactivated, conflictUsername, skippedUnverifiedEmail
   */

  public static function classify($person, $usernameAccount, $emailAccounts) {
    $ret = array(
      'outcome'                => self::OUTCOME_CREATE,
      'account'                => null,
      'rule'                   => null,
      'deactivated'            => false,
      'conflictUsername'       => null,
      'skippedUnverifiedEmail' => false
    );

    $candidates = array();
    if(!empty($usernameAccount)) {
      $candidates[] = $usernameAccount;
    }
    foreach($emailAccounts as $a) {
      $candidates[] = $a;
    }

    // Login identity first, over every candidate.
    foreach($candidates as $a) {
      if(self::matchesLogin($person, $a)) {
        return self::owned($ret, $a, self::RULE_LOGIN);
      }
    }

    // Then exact email, but only for a verified Registry email.
    foreach($candidates as $a) {
      if(self::emailsMatch($person['email'] ?? null, $a['email'] ?? null)) {
        if(empty($person['emailVerified'])) {
          $ret['skippedUnverifiedEmail'] = true;
          continue;
        }

        return self::owned($ret, $a, self::RULE_EMAIL);
      }
    }

    if(!empty($usernameAccount)) {
      $ret['outcome'] = self::OUTCOME_CONFLICT;
      $ret['conflictUsername'] = self::username($usernameAccount);
    }

    return $ret;
  }

  /**
   * Determine whether a single account belongs to a CO Person.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array  $person  CO Person facts
   * @param  Array  $account Dataverse account
   * @return String RULE_LOGIN or RULE_EMAIL, or null if the account is not theirs
   */

  public static function ownsAccount($person, $account) {
    if(self::matchesLogin($person, $account)) {
      return self::RULE_LOGIN;
    }

    if(!empty($person['emailVerified'])
       && self::emailsMatch($person['email'] ?? null, $account['email'] ?? null)) {
      return self::RULE_EMAIL;
    }

    return null;
  }

  /**
   * Compare two email addresses exactly, ignoring case and surrounding space.
   *
   * @since  COmanage Registry v4.3.5
   * @param  String  $a Email address
   * @param  String  $b Email address
   * @return Boolean True if both are non-empty and equal
   */

  public static function emailsMatch($a, $b) {
    if(!is_string($a) || !is_string($b)) {
      return false;
    }

    $a = strtolower(trim($a));
    $b = strtolower(trim($b));

    return $a !== '' && $a === $b;
  }

  /**
   * Return the username of a Dataverse account without the leading '@'.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array  $account Dataverse account from GET by username or from list-users
   * @return String Username, or null if the account carries none
   */

  public static function username($account) {
    $u = $account['identifier'] ?? ($account['userIdentifier'] ?? null);

    if(!is_string($u) || $u === '') {
      return null;
    }

    return ltrim($u, '@');
  }

  /**
   * Parse a ProvisioningTarget Identifier value into a link state.
   *
   * Links made before the ownership rules are "<targetId>:<dataverseId>".
   * Links made under them are "<targetId>:<dataverseId>:v2".
   *
   * @since  COmanage Registry v4.3.5
   * @param  String  $value                  Identifier value
   * @param  Integer $coProvisioningTargetId Provisioning Target ID
   * @return Array   state and Dataverse id
   */

  public static function parseLink($value, $coProvisioningTargetId) {
    $none = array('state' => self::LINK_NONE, 'id' => null);

    if(!is_string($value) || $value === '') {
      return $none;
    }

    $parts = explode(':', $value);

    if(count($parts) < 2 || count($parts) > 3
       || $parts[0] !== (string)$coProvisioningTargetId
       || !ctype_digit($parts[1])) {
      return $none;
    }

    if(count($parts) == 2) {
      return array('state' => self::LINK_PREFIX, 'id' => (int)$parts[1]);
    }

    if($parts[2] === self::LINK_VERSION) {
      return array('state' => self::LINK_TRUSTED, 'id' => (int)$parts[1]);
    }

    return $none;
  }

  /**
   * Format the Identifier value for a link made under the ownership rules.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Integer $coProvisioningTargetId Provisioning Target ID
   * @param  Integer $dataverseId            Dataverse account id
   * @return String  Identifier value
   */

  public static function formatLink($coProvisioningTargetId, $dataverseId) {
    return $coProvisioningTargetId . ':' . $dataverseId . ':' . self::LINK_VERSION;
  }

  /**
   * List the ways a CO Group identifier may be read as a DOI, in the order
   * they should be tried: the full identifier first, then the identifier
   * with a trailing "-<suffix>" removed.
   *
   * @since  COmanage Registry v4.3.5
   * @param  String $rawIdentifier CO Group identifier value
   * @return Array  List of arrays with keys doi and suffix
   */

  public static function doiInterpretations($rawIdentifier) {
    $ret = array(array('doi' => $rawIdentifier, 'suffix' => null));

    $dashPos = strrpos($rawIdentifier, '-');
    if($dashPos !== false) {
      $suffix = substr($rawIdentifier, $dashPos + 1);

      if($suffix !== '') {
        $ret[] = array('doi' => substr($rawIdentifier, 0, $dashPos), 'suffix' => $suffix);
      }
    }

    return $ret;
  }

  /**
   * Determine whether an account carries the CO Person's login identity.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array   $person  CO Person facts
   * @param  Array   $account Dataverse account
   * @return Boolean True if provider and persistent user ID both match
   */

  protected static function matchesLogin($person, $account) {
    $provider = $person['providerId'] ?? null;
    $puid = $person['persistentUserId'] ?? null;

    return !empty($provider) && !empty($puid)
           && ($account['authenticationProviderId'] ?? null) === $provider
           && ($account['persistentUserId'] ?? null) === $puid;
  }

  /**
   * Fill in an owned outcome.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array  $ret     Outcome being built
   * @param  Array  $account Owned account
   * @param  String $rule    Rule that matched
   * @return Array  Outcome
   */

  protected static function owned($ret, $account, $rule) {
    $ret['outcome'] = self::OUTCOME_OWNED;
    $ret['account'] = $account;
    $ret['rule'] = $rule;
    $ret['deactivated'] = !empty($account['deactivated']);

    return $ret;
  }
}
