<?php
/**
 * COmanage Registry Dataverse Provisioner Audit Shell
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
 * Read-only report of Dataverse group access that may sit on the wrong
 * Dataverse account. Run from the Registry app directory:
 *
 *   Console/cake DataverseProvisioner.DataverseAudit <target id>
 *
 * where <target id> is the ID of the CO Dataverse Provisioner Target.
 * Nothing in Registry or Dataverse is changed.
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.3.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('AppShell', 'Console/Command');

class DataverseAuditShell extends AppShell {
  public $uses = array('DataverseProvisioner.CoDataverseProvisionerTarget');

  /**
   * Define the command line arguments.
   *
   * @since  COmanage Registry v4.3.5
   * @return ConsoleOptionParser
   */

  public function getOptionParser() {
    $parser = parent::getOptionParser();

    $parser->addArgument(
      'targetId',
      array(
        'help'     => 'ID of the CO Dataverse Provisioner Target to audit',
        'required' => true
      )
    )->description('Read-only report of Dataverse group access that may sit on the wrong Dataverse account');

    return $parser;
  }

  /**
   * Run the audit and print one line per finding.
   *
   * @since  COmanage Registry v4.3.5
   */

  public function main() {
    $findings = $this->CoDataverseProvisionerTarget->audit($this->args[0]);

    $counts = array('person' => 0, 'unexpected' => 0, 'error' => 0);

    foreach($findings as $f) {
      $counts[$f['kind']]++;

      $line = strtoupper($f['kind'])
              . "\tCO Group " . $f['coGroupId'] . " (" . $f['coGroupName'] . ")";

      if(!is_null($f['coPersonId'])) {
        $line .= "\tCO Person " . $f['coPersonId'];
      }

      if(!is_null($f['account'])) {
        $line .= "\taccount " . $f['account'];
      }

      if(!is_null($f['inGroup'])) {
        $line .= "\t" . ($f['inGroup'] ? "IN Dataverse group" : "not in Dataverse group");
      }

      $line .= "\t" . $f['comment'];

      $this->out($line);
    }

    $this->out("Totals: " . $counts['person'] . " CO Person conflicts, "
               . $counts['unexpected'] . " unexpected Dataverse group members, "
               . $counts['error'] . " groups that could not be checked");
  }
}
