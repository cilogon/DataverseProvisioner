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

    $this->assertNotSame(DataverseOwnership::OUTCOME_OWNED, $result['outcome']);
    $this->assertTrue($result['skippedUnverifiedEmail']);
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
