<?php
/**
 * COmanage Registry CO Dataverse Provisioner Target Model
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
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");

class CoDataverseProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoDataverseProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoProvisioningTarget",
    "Server"
  );
  
  // Default display field for cake generated views
  public $displayField = "server_id";
  
  // Request Http servers
  public $cmServerType = ServerEnum::HttpServer;
  
  // Instance of CoHttpClient
  protected $Http = null;

  // Dataverse API query string parameter
  protected $unblockKey = null;
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'server_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'unfreeze' => 'CO'
      )
    ),
    'authentication_provider_id' => array(
      'content' => array(
        'rule' => 'notBlank',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'persistent_user_id_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'identifier_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'name_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Name.type',
                              'default' => array(NameEnum::Alternate,
                                                 NameEnum::Author,
                                                 NameEnum::FKA,
                                                 NameEnum::Official,
                                                 NameEnum::Preferred))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'email_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'EmailAddress.type',
                              'default' => array(EmailAddressEnum::Delivery,
                                                 EmailAddressEnum::Forwarding,
                                                 EmailAddressEnum::MailingList,
                                                 EmailAddressEnum::Official,
                                                 EmailAddressEnum::Personal,
                                                 EmailAddressEnum::Preferred,
                                                 EmailAddressEnum::Recovery))),
        'required' => true,
        'allowEmpty' => false
      )
    )
  );

  /**
   * Add Dataverse ID Identifier of type IdentifierEnum::ProvisioningTarget
   *
   * @since  COmanage Registry v4.0.0
   * @param  Integer  $dataverseId             Dataverse ID
   * @param  Integer  $coPersonId              CO Person ID
   * @param  Integer  $coProvisioningTargetId  Provisioning Target ID
   * @return none                           
   * @throws RuntimeException
   */

  protected function addDataverseIdIdentifier($dataverseId, $coPersonId, $coProvisioningTargetId) {
    $args = array();
    $args['Identifier']['identifier'] = $dataverseId;
    $args['Identifier']['co_person_id'] = $coPersonId;
    $args['Identifier']['type'] = IdentifierEnum::ProvisioningTarget;
    $args['Identifier']['login'] = false;
    $args['Identifier']['status'] = SuspendableStatusEnum::Active;
    $args['Identifier']['co_provisioning_target_id'] = $coProvisioningTargetId;

    $this->CoProvisioningTarget->Co->CoPerson->Identifier->clear();
    $this->CoProvisioningTarget->Co->CoPerson->Identifier->save($args);
  }

  /**
   * Create an Authenticated User in Dataverse for a CO Person.
   * 
   * @since  COmanage Registry v4.3.3
   * @param  Array            $coProvisioningTargetData  CoProvisioningTargetData
   * @param  Array            $provisioningData          provisioning data
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function createAuthenticatedUser($coProvisioningTargetData, $provisioningData) {
    $authenticatedUser = array();
    $coPersonId = $provisioningData['CoPerson']['id'];
    $coProvisioningTargetId = $coProvisioningTargetData['CoDataverseProvisionerTarget']['id'];

    // We only create authenticated users for active CO Person records.
    $status = $provisioningData['CoPerson']['status'];
    if($status != StatusEnum::Active) {
      return false;
    }

    // Find the Dataverse identifier.
    $identifierType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['identifier_type'];

    $dataverseIdentifier = null;
    foreach ($provisioningData['Identifier'] as $identifier) {
      if($identifier['type'] == $identifierType) {
        $dataverseIdentifier = $identifier['identifier'];
        break;
      }
    }

    // We cannot provision an authenticated user without a Dataverse identifier.
    if(is_null($dataverseIdentifier)) {
      return false;
    }

    // Skip over CO person records that are already provisioned.
    $this->createHttpClient($coProvisioningTargetData);
    if(!empty($this->getAuthenticatedUserByIdentifier($dataverseIdentifier))) {
      return true;
    }

    $authenticatedUser['identifier'] = $dataverseIdentifier;

    // Find the persistent user identifier.
    $persistentUserIdType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['persistent_user_id_type'];

    $persistentUserId = null;
    foreach ($provisioningData['Identifier'] as $identifier) {
      if($identifier['type'] == $persistentUserIdType) {
        $persistentUserId = $identifier['identifier'];
        break;
      }
    }

    // We cannot provision an authenticated user without a persistent user ID.
    if(is_null($persistentUserId)) {
      return false;
    }

    $authenticatedUser['persistentUserId'] = $persistentUserId;

    // Find the Name data.
    $nameType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['name_type'];
    $namei = null;

    foreach ($provisioningData['Name'] as $i => $name) {
      if($name['type'] == $nameType) {
        $namei = $i;
        break;
      }
    }

    // We cannot provision without name data.
    if(is_null($namei)) {
      return false;
    }

    $authenticatedUser['firstName'] = $provisioningData['Name'][$namei]['given'] ?? 'none';
    $authenticatedUser['lastName'] = $provisioningData['Name'][$namei]['family'] ?? 'none';

    // Find the EmailAddress data.
    $emailType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['email_type'];
    $emaili = null;

    foreach ($provisioningData['EmailAddress'] as $i => $email) {
      if($email['type'] == $emailType) {
        $emaili = $i;
        break;
      }
    }

    // We cannot provision without email data.
    if(is_null($emaili)) {
      return false;
    }

    $authenticatedUser['email'] = $provisioningData['EmailAddress'][$emaili]['mail'];

    $authenticatedUser['authenticationProviderId'] = $coProvisioningTargetData['CoDataverseProvisionerTarget']['authentication_provider_id'];

    // Provision the authenticated user in Dataverse.
    $path = "/api/admin/authenticatedUsers?unblock-key=" . $this->unblockKey;
    $response = $this->Http->post($path, json_encode($authenticatedUser));

    if($response->code != 200) {
      return false;
    }

    // Record the Dataverse ID for the newly created authenticated user.
    $dataverseId = json_decode($response->body, true)['data']['id'];

    // Find any existing Identifier of type IdentifierEnum::ProvisioningTarget
    // and reconcile.
    $dataverseIdIdentifier = $this->getDataverseIdIdentifier($id, $coProvisioningTargetId);

    if(is_null($dataverseIdIdentifier)) {
      $this->addDataverseIdIdentifier($dataverseId, $coPersonId, $coProvisioningTargetId);
    } elseif ($dataverseIdIdentifier['Identifier']['id'] != $dataverseId) {
      $this->deleteDataverseIdIdentifier($dataverseIdIdentifier['Identifier']['id']);
      $this->addDataverseIdIdentifier($dataverseId, $coPersonId, $coProvisioningTargetId);
    }

    return true;
  }
  
  /**
   * Create HTTP client connected to Dataverse server
   *
   * @since   COmanage Registry v4.0.0
   * @param   Array $coProvisioningTargetData Provisioning target data as passed to provision function
   * @return  Void
   * @throws  InvalidArgumentException
   *
   */

  protected function createHttpClient($coProvisioningTargetData) {
      $args = array();
      $args['conditions']['Server.id'] = $coProvisioningTargetData['CoDataverseProvisionerTarget']['server_id'];
      $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = array('HttpServer');

      $CoProvisioningTarget = new CoProvisioningTarget();
      $srvr = $CoProvisioningTarget->Co->Server->find('first', $args);

      if(empty($srvr)) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.http_servers.1'), $coProvisioningTargetData['CoDataverseProvisionerTarget']['server_id'])));
      }
      
      $this->Http = new CoHttpClient();
      
      $this->Http->setConfig($srvr['HttpServer']);

      $this->Http->setRequestOptions(array(
        'header' => array(
          'Accept'          => 'application/json',
          'Content-Type'    => 'application/json; charset=UTF-8',
          'X-Dataverse-key' => $srvr['HttpServer']['password']
        )
      ));

      $this->unblockKey = $srvr['HttpServer']['password'];
  }

  /**
   * Delete Dataverse ID Identifier of type IdentifierEnum::ProvisioningTarget
   *
   * @since  COmanage Registry v4.0.0
   * @param  Integer  $id                      Identifier id
   * @return none                           
   * @throws RuntimeException
   */

  protected function deleteDataverseIdIdentifier($id) {
    $this->CoProvisioningTarget->Co->CoPerson->Identifier->clear();
    $this->CoProvisioningTarget->Co->CoPerson->Identifier->delete($id);
  }

  /**
   * Get Dataverse authenticated user using Dataverse identifier
   *
   * @since  COmanage Registry v4.0.0
   * @param  String  $identifier             Dataverse identifier
   * @return Array                           Array of authenticated user details 
   * @throws RuntimeException
   */
  protected function getAuthenticatedUserByIdentifier($identifier) {
    $authenticatedUser = array();

    $path = "/api/admin/authenticatedUsers/" . $identifier;
    $query = array('unblock-key' => $this->unblockKey);
    $response = $this->Http->get($path, $query);

    if($response->code == 200) {
      $authenticatedUser = json_decode($response->body, true)['data'];
    }

    return $authenticatedUser;
  }

  /**
   * Get Dataverse ID Identifier of type IdentifierEnum::ProvisioningTarget
   *
   * @since  COmanage Registry v4.0.0
   * @param  Integer  $coPersonId              CO Person ID
   * @param  Integer  $coProvisioningTargetId  Provisioning Target ID
   * @return Array                             Dataverse ID Identifier of type IdentifierEnum::ProvisioningTarget
   * @throws RuntimeException
   */
  protected function getDataverseIdIdentifier($coPersonId, $coProvisioningTargetId) {
    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $coPersonId;
    $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
    $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['contain'] = false;

    $dataverseIdIdentifier = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);

    if(!empty($dataverseIdIdentifier)) {
      return $dataverseIdIdentifier;
    } else {
      return null;
    }
  }

  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Array                  $coProvisioningTargetData CO Provisioning Target data
   * @param  ProvisioningActionEnum $op                       Registry transaction type triggering provisioning
   * @param  Array                  $provisioningData         Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    // First determine what to do
    $deletePerson = false;
    $syncPerson = false;

    switch($op) {
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
        $this->createAuthenticatedUser($coProvisioningTargetData, $provisioningData);

//      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
//      case ProvisioningActionEnum::CoPersonExpired:
//      case ProvisioningActionEnum::CoPersonUnexpired:
//      case ProvisioningActionEnum::CoPersonUpdated:
//        if($provisioningData['CoPerson']['status'] == StatusEnum::Deleted) {
//          $deletePerson = true;
//        } else {
//          $syncPerson = true;
//        }
//        break;
//      case ProvisioningActionEnum::CoPersonDeleted:
//        $deletePerson = true;
//        break;
      default:
        // Ignore all other actions. Note group membership changes
        // are typically handled as CoPersonUpdated events.
        return true;
        break;
    }
  }

  /**
   * Determine the provisioning status of this target.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Integer $coProvisioningTargetId CO Provisioning Target ID
   * @param  Model   $Model                  Model being queried for status (eg: CoPerson, CoGroup,
   *                                         CoEmailList, COService)
   * @param  Integer $id                     $Model ID to check status for
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */

  public function status($coProvisioningTargetId, $model, $id) {
    $ret = array();
    $ret['status'] = ProvisioningStatusEnum::NotProvisioned;
    $ret['timestamp'] = null;
    $ret['comment'] = "";

    // Pull the provisioning target configuration.
    $args = array();
    $args['conditions']['CoDataverseProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['contain'] = false;

    $coProvisioningTargetData = $this->find('first', $args);

    $this->createHttpClient($coProvisioningTargetData);

    if($model->name == 'CoPerson') {
      $identifierType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['identifier_type'];

      // Pull the CO Person record.
      $args = array();
      $args['conditions']['CoPerson.id'] = $id;
      $args['contain'] = array();
      $args['contain'][] = 'Identifier';

      $coPerson = $this->CoProvisioningTarget->Co->CoPerson->find('first', $args);

      // Find the Dataverse identifier.
      $dataverseIdentifier = null;
      foreach($coPerson['Identifier'] as $identifier) {
        if($identifier['type'] == $identifierType && $identifier['status'] = SuspendableStatusEnum::Active) {
          $dataverseIdentifier = $identifier['identifier'];
        }
      }

      if(is_null($dataverseIdentifier)) {
        $ret['comment'] = "No Identifier of type " . $identifierType . " for CO Person";
        return $ret;
      }

      // Query Dataverse server for authenticated user object.
      $authenticatedUser = $this->getAuthenticatedUserByIdentifier($dataverseIdentifier);

      if(!empty($authenticatedUser)) {
        // The user is provisioned.
        $ret['status'] = ProvisioningStatusEnum::Provisioned;
        $ret['comment'] = $authenticatedUser['deactivated'] ? "User is deactivated in Dataverse" : "User is active in Dataverse";
        $ret['timestamp'] = $authenticatedUser['createdTime'];
      }

      // Find any existing Identifier of type IdentifierEnum::ProvisioningTarget.
      $dataverseIdIdentifier = $this->getDataverseIdIdentifier($id, $coProvisioningTargetId);

      if(!empty($authenticatedUser) && is_null($dataverseIdIdentifier)) {
        $this->addDataverseIdIdentifier($authenticatedUser['id'], $id, $coProvisioningTargetId);
      } elseif (!empty($authenticatedUser) && ($dataverseIdIdentifier['Identifier']['identifier'] != $authenticatedUser['id'])) {
        $this->deleteDataverseIdIdentifier($dataverseIdIdentifier['Identifier']['id']);
        $this->addDataverseIdIdentifier($authenticatedUser['id'], $id, $coProvisioningTargetId);
      } elseif(empty($authenticatedUser) && !is_null($dataverseIdIdentifier)) {
        $this->deleteDataverseIdIdentifier($dataverseIdIdentifier['Identifier']['id']);
      }
    }

    return $ret;
  }
}
