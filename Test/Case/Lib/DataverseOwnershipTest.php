<?php
/**
 * Unit tests for DataverseOwnership
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
 * These tests run with plain PHPUnit outside COmanage Registry (see
 * phpunit.xml.dist). They are not CakePHP test cases.
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/Lib/DataverseOwnership.php';

class DataverseOwnershipTest extends TestCase {
  const PROVIDER = 'oidc-cilogon';

  // A CO Person as the model hands it to DataverseOwnership.
  protected function person($overrides = array()) {
    return array_merge(array(
      'providerId'       => self::PROVIDER,
      'persistentUserId' => 'http://cilogon.org/serverT/users/111',
      'email'            => 'b.person@example.org',
      'emailVerified'    => true,
      'username'         => 'RNadal'
    ), $overrides);
  }

  // A Dataverse authenticated user as GET /api/admin/authenticatedUsers/{id} returns it.
  protected function account($overrides = array()) {
    return array_merge(array(
      'id'                       => 42,
      'identifier'               => '@RNadal',
      'email'                    => 'a.person@example.org',
      'authenticationProviderId' => self::PROVIDER,
      'persistentUserId'         => 'http://cilogon.org/serverT/users/999',
      'deactivated'              => false
    ), $overrides);
  }

  public function testUsernameCollisionWithAnotherPersonIsAConflict() {
    // AE1: the account at B's username belongs to A.
    $result = DataverseOwnership::classify($this->person(), $this->account(), array());

    $this->assertSame(DataverseOwnership::OUTCOME_CONFLICT, $result['outcome']);
    $this->assertSame('RNadal', $result['conflictUsername']);
    $this->assertNull($result['account']);
  }

  public function testLoginIdentityMatchOwnsTheAccount() {
    $account = $this->account(array('persistentUserId' => 'http://cilogon.org/serverT/users/111'));

    $result = DataverseOwnership::classify($this->person(), $account, array());

    $this->assertSame(DataverseOwnership::OUTCOME_OWNED, $result['outcome']);
    $this->assertSame(DataverseOwnership::RULE_LOGIN, $result['rule']);
    $this->assertSame(42, $result['account']['id']);
  }

  public function testLoginIdentityRequiresTheSameProvider() {
    $account = $this->account(array(
      'persistentUserId'         => 'http://cilogon.org/serverT/users/111',
      'authenticationProviderId' => 'builtin'
    ));

    $result = DataverseOwnership::classify($this->person(), $account, array());

    $this->assertSame(DataverseOwnership::OUTCOME_CONFLICT, $result['outcome']);
  }

  public function testVerifiedExactEmailOwnsAPreExistingAccount() {
    // AE2: B's pre-existing account has a different username and B's email.
    $emailAccount = $this->account(array(
      'id'               => 7,
      'identifier'       => '@rnadal_b',
      'email'            => 'b.person@example.org',
      'persistentUserId' => 'shib-123',
      'authenticationProviderId' => 'shib'
    ));

    $result = DataverseOwnership::classify($this->person(), null, array($emailAccount));

    $this->assertSame(DataverseOwnership::OUTCOME_OWNED, $result['outcome']);
    $this->assertSame(DataverseOwnership::RULE_EMAIL, $result['rule']);
    $this->assertSame(7, $result['account']['id']);
  }

  public function testUnverifiedRegistryEmailDoesNotOwn() {
    $emailAccount = $this->account(array(
      'id'         => 7,
      'identifier' => '@rnadal_b',
      'email'      => 'b.person@example.org'
    ));

    $result = DataverseOwnership::classify($this->person(array('emailVerified' => false)), null, array($emailAccount));

    $this->assertSame(DataverseOwnership::OUTCOME_CREATE, $result['outcome']);
    $this->assertTrue($result['skippedUnverifiedEmail']);
    $this->assertNull(DataverseOwnership::ownsAccount($this->person(array('emailVerified' => false)), $emailAccount));
  }

  public function testUnverifiedEmailOnAnotherPersonsUsernameAccountIsAConflict() {
    // The account at the username carries the CO Person's email, but the email is unverified.
    $account = $this->account(array('email' => 'b.person@example.org'));

    $result = DataverseOwnership::classify($this->person(array('emailVerified' => false)), $account, array($account));

    $this->assertSame(DataverseOwnership::OUTCOME_CONFLICT, $result['outcome']);
    $this->assertSame('RNadal', $result['conflictUsername']);
    $this->assertTrue($result['skippedUnverifiedEmail']);
  }

  public function testEmptyLoginFactsNeverMatch() {
    $account = $this->account(array('persistentUserId' => null, 'authenticationProviderId' => ''));

    $this->assertNull(DataverseOwnership::ownsAccount($this->person(array('persistentUserId' => null)), $account));
    $this->assertNull(DataverseOwnership::ownsAccount($this->person(array('providerId' => '', 'persistentUserId' => null)), $account));
  }

  /**
   * Cases for resolveLinkState: link value, account at the username, and whether it is the CO Person's.
   */

  public static function linkStateCases() {
    $owned = array('id' => 42, 'identifier' => '@RNadal', 'email' => 'b.person@example.org',
                   'authenticationProviderId' => self::PROVIDER, 'persistentUserId' => 'http://cilogon.org/serverT/users/111');
    $other = array('id' => 42, 'identifier' => '@RNadal', 'email' => 'a.person@example.org',
                   'authenticationProviderId' => self::PROVIDER, 'persistentUserId' => 'http://cilogon.org/serverT/users/999');
    $elsewhere = array_merge($owned, array('id' => 43));

    return array(
      'trusted, same account'            => array('12:42:v2', $owned, DataverseOwnership::STATE_TRUSTED),
      'trusted, same account not owned'  => array('12:42:v2', $other, DataverseOwnership::STATE_TRUSTED),
      'trusted, different account'       => array('12:42:v2', $elsewhere, DataverseOwnership::STATE_CONFLICT),
      'trusted, username free'           => array('12:42:v2', null, DataverseOwnership::STATE_CONFLICT),
      'prefix, same account owned'       => array('12:42', $owned, DataverseOwnership::STATE_PREFIX),
      'prefix, same account not owned'   => array('12:42', $other, DataverseOwnership::STATE_CONFLICT),
      'prefix, different account'        => array('12:42', $elsewhere, DataverseOwnership::STATE_CONFLICT),
      'prefix, username free'            => array('12:42', null, DataverseOwnership::STATE_CONFLICT),
      'none, account not owned'          => array(null, $other, DataverseOwnership::STATE_CONFLICT),
      'none, account owned'              => array(null, $owned, DataverseOwnership::STATE_NONE),
      'none, username free'              => array(null, null, DataverseOwnership::STATE_NONE)
    );
  }

  #[PHPUnit\Framework\Attributes\DataProvider('linkStateCases')]
  public function testResolveLinkState($linkValue, $usernameAccount, $expectedState) {
    $link = DataverseOwnership::parseLink($linkValue, 12);

    $result = DataverseOwnership::resolveLinkState($this->person(), $link, $usernameAccount);

    $this->assertSame($expectedState, $result['state']);

    if($expectedState == DataverseOwnership::STATE_TRUSTED || $expectedState == DataverseOwnership::STATE_PREFIX) {
      $this->assertSame($usernameAccount, $result['account']);
    } else {
      $this->assertNull($result['account']);
    }

    if($expectedState == DataverseOwnership::STATE_CONFLICT) {
      $this->assertStringStartsWith('Conflict:', $result['comment']);
    }
  }

  public function testPrefixEmailMatchDoesNotOwn() {
    // AE3: list-users is a prefix search, so ab@example.org finds ab@example.org.au.
    $emailAccount = $this->account(array('id' => 8, 'identifier' => '@other', 'email' => 'ab@example.org.au'));

    $result = DataverseOwnership::classify($this->person(array('email' => 'ab@example.org')), null, array($emailAccount));

    $this->assertSame(DataverseOwnership::OUTCOME_CREATE, $result['outcome']);
  }

  public function testEmailComparisonIgnoresCaseAndSurroundingSpace() {
    $this->assertTrue(DataverseOwnership::emailsMatch('Bob@Example.org', ' bob@example.org'));
    $this->assertFalse(DataverseOwnership::emailsMatch('bob@example.org', 'bob@example.org.au'));
    $this->assertFalse(DataverseOwnership::emailsMatch('', ''));
    $this->assertFalse(DataverseOwnership::emailsMatch(null, 'bob@example.org'));
  }

  public function testLoginMatchWinsOverADifferentEmailMatch() {
    $emailAccount = $this->account(array('id' => 7, 'identifier' => '@rnadal_b', 'email' => 'b.person@example.org'));
    $loginAccount = $this->account(array(
      'id'               => 9,
      'identifier'       => '@bperson',
      'persistentUserId' => 'http://cilogon.org/serverT/users/111'
    ));

    $result = DataverseOwnership::classify($this->person(), null, array($emailAccount, $loginAccount));

    $this->assertSame(DataverseOwnership::RULE_LOGIN, $result['rule']);
    $this->assertSame(9, $result['account']['id']);
  }

  public function testOwnedDeactivatedAccountIsReported() {
    $account = $this->account(array(
      'persistentUserId' => 'http://cilogon.org/serverT/users/111',
      'deactivated'      => true
    ));

    $result = DataverseOwnership::classify($this->person(), $account, array());

    $this->assertSame(DataverseOwnership::OUTCOME_OWNED, $result['outcome']);
    $this->assertTrue($result['deactivated']);
  }

  public function testNoCandidatesMeansCreate() {
    $result = DataverseOwnership::classify($this->person(), null, array());

    $this->assertSame(DataverseOwnership::OUTCOME_CREATE, $result['outcome']);
    $this->assertNull($result['account']);
  }

  public function testOwnsAccountAppliesTheSameRules() {
    $person = $this->person();

    $this->assertSame(DataverseOwnership::RULE_LOGIN,
                      DataverseOwnership::ownsAccount($person, $this->account(array('persistentUserId' => 'http://cilogon.org/serverT/users/111'))));
    $this->assertSame(DataverseOwnership::RULE_EMAIL,
                      DataverseOwnership::ownsAccount($person, $this->account(array('email' => 'B.Person@example.org'))));
    $this->assertNull(DataverseOwnership::ownsAccount($person, $this->account()));
  }

  public function testUsernameStripsTheLeadingAt() {
    $this->assertSame('RNadal', DataverseOwnership::username(array('identifier' => '@RNadal')));
    $this->assertSame('rnadal_b', DataverseOwnership::username(array('userIdentifier' => 'rnadal_b')));
    $this->assertNull(DataverseOwnership::username(array()));
  }

  public function testParseLink() {
    $this->assertSame(array('state' => DataverseOwnership::LINK_PREFIX, 'id' => 345),
                      DataverseOwnership::parseLink('12:345', 12));
    $this->assertSame(array('state' => DataverseOwnership::LINK_TRUSTED, 'id' => 345),
                      DataverseOwnership::parseLink('12:345:v2', 12));

    foreach(array(null, '', '12', '12:abc', '13:345', '13:345:v2', '12:345:v3', 'x:12:345') as $value) {
      $this->assertSame(array('state' => DataverseOwnership::LINK_NONE, 'id' => null),
                        DataverseOwnership::parseLink($value, 12),
                        "value " . var_export($value, true));
    }
  }

  public function testFormatLink() {
    $this->assertSame('12:345:v2', DataverseOwnership::formatLink(12, 345));
    $this->assertSame('12:345', DataverseOwnership::formatPrefixLink(12, 345));
  }

  public function testDoiInterpretationsTryTheFullIdentifierFirst() {
    // AE6
    $this->assertSame(array(array('doi' => '10.12345/ABC-DEF', 'suffix' => null),
                            array('doi' => '10.12345/ABC', 'suffix' => 'DEF')),
                      DataverseOwnership::doiInterpretations('10.12345/ABC-DEF'));
    $this->assertSame(array(array('doi' => '10.12345/XYZ-Restricted', 'suffix' => null),
                            array('doi' => '10.12345/XYZ', 'suffix' => 'Restricted')),
                      DataverseOwnership::doiInterpretations('10.12345/XYZ-Restricted'));
    $this->assertSame(array(array('doi' => '10.12345/XYZ', 'suffix' => null)),
                      DataverseOwnership::doiInterpretations('10.12345/XYZ'));
    $this->assertSame(array(array('doi' => '10.12345/XYZ-', 'suffix' => null)),
                      DataverseOwnership::doiInterpretations('10.12345/XYZ-'));
  }
}
