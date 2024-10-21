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
 * @since         COmanage Registry v4.3.4
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
  
  // Instance of CoHttpClient for Dataverse server
  protected $Http = null;

  // Instance of CoHttpClient for DOI API server
  protected $Doi = null;

  // Dataverse API query string parameter
  protected $unblockKey = null;

  // Active ID used in logging
  protected $activeId;

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
    'doi_server_id' => array(
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
                              'default' => array(IdentifierEnum::AffiliateSOR, 
                                                 IdentifierEnum::Badge,
                                                 IdentifierEnum::Enterprise,
                                                 IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::ePUID,
                                                 IdentifierEnum::GID,
                                                 IdentifierEnum::GuestSOR,
                                                 IdentifierEnum::HRSOR,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::National,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::ORCID,
                                                 IdentifierEnum::ProvisioningTarget,
                                                 IdentifierEnum::Reference,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::StudentSOR,
                                                 IdentifierEnum::SORID,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'identifier_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::AffiliateSOR,
                                                 IdentifierEnum::Badge,
                                                 IdentifierEnum::Enterprise,
                                                 IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::ePUID,
                                                 IdentifierEnum::GID,
                                                 IdentifierEnum::GuestSOR,
                                                 IdentifierEnum::HRSOR,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::National,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::ORCID,
                                                 IdentifierEnum::ProvisioningTarget,
                                                 IdentifierEnum::Reference,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::StudentSOR,
                                                 IdentifierEnum::SORID,
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
    ),
    'group_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::AffiliateSOR,
                                                 IdentifierEnum::Badge,
                                                 IdentifierEnum::Enterprise,
                                                 IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::ePUID,
                                                 IdentifierEnum::GID,
                                                 IdentifierEnum::GuestSOR,
                                                 IdentifierEnum::HRSOR,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::National,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::ORCID,
                                                 IdentifierEnum::ProvisioningTarget,
                                                 IdentifierEnum::Reference,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::StudentSOR,
                                                 IdentifierEnum::SORID,
                                                 IdentifierEnum::UID))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'skip_doi' => array(
      'rule' => array('boolean')
    )
  );

  /**
   * Add Dataverse ID Identifier of type IdentifierEnum::ProvisioningTarget
   *
   * @since  COmanage Registry v4.3.4
   * @param  Integer  $dataverseId             Dataverse ID
   * @param  Integer  $coPersonId              CO Person ID
   * @param  Integer  $coProvisioningTargetId  Provisioning Target ID
   * @return none                           
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
   * Map CO Group to owner dataverse and explicit group alias combination
   *
   * @since  COmanage Registry v4.3.4
   * @param  Array $coProvisioningTargetData CO Provisioning Target data
   * @param  Array $coGroup                  CO Group data
   * @return Array array of owner dataverse, explicit group alias, and comment on error                         
   * @throws InvalidArgumentException
   */

  protected function coGroupToOwnerDataverse($coProvisioningTargetData, $coGroup) {
    $ret = array();
    $ret['ownerDataverseAlias'] = null;
    $ret['explicitGroupAlias'] = null;
    $ret['comment'] = "";

    $groupIdentifierType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['group_type'];

    // Find the Dataverse group Identifier for the CO Group which holds the DOI.
    $doi = null;
    foreach($coGroup['Identifier'] as $identifier) {
      if ($identifier['type'] == $groupIdentifierType && $identifier['status'] = SuspendableStatusEnum::Active) {
        $doi = $identifier['identifier'];
      }
    }

    if(is_null($doi)) {
      $ret['comment'] = "No Identifier of type " . $groupIdentifierType . " for CO Group";
      return $ret;
    }

    // If configured skip using the DOI API to map the DOI to a specific
    // Dataverse Server instance and instead just assume that the CO Group with
    // this DOI is intended to be provisioned to our configured Dataverse server.
    //
    // This option is useful when testing since the sandbox Dataverse servers
    // may not actually publish DOIs in a way that can be resolved using the
    // DOI API.
    $skipDoiMapping = $coProvisioningTargetData['CoDataverseProvisionerTarget']['skip_doi'] ?? false;
    if(!$skipDoiMapping) {
      // Exchange the DOI using the DOI API for a server host.
      $doiMappedServerHost = $this->doiToMappedServerHost($doi, $coProvisioningTargetData);

      if(is_null($doiMappedServerHost)) {
        $ret['comment'] = "Unable to map DOI to Dataverse server";
        return $ret;
      }

      // Find our configured Dataserver host.
      $args = array();
      $args['conditions']['Server.id'] = $coProvisioningTargetData['CoDataverseProvisionerTarget']['server_id'];
      $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = array('HttpServer');

      $CoProvisioningTarget = new CoProvisioningTarget();
      $srvr = $CoProvisioningTarget->Co->Server->find('first', $args);

      if(empty($srvr)) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.http_servers.1'), $coProvisioningTargetData['CoDataverseProvisionerTarget']['server_id'])));
      }

      $myServerHost = parse_url($srvr['HttpServer']['serverurl'])['host'];

      // If the server mapped from the DOI is not the same as our server then return.
      if($myServerHost != $doiMappedServerHost) {
        $ret['comment'] = "DOI does not map to this Dataverse server";
        return $ret;
      }
    }

    // Query the Dataverse server with the DOI persistent ID to find the owner dataverse.
    $ret['ownerDataverseAlias'] = $this->doiToOwnerDataverseAlias($doi);

    // The group alias in the owning dataverse/collection is constructed from the DOI.
    $ret['explicitGroupAlias'] = "authorized_" . str_replace(array(".", "/"), array("_", "_"), $doi);

    return $ret;
  }

  /**
   * Create an Authenticated User in Dataverse for a CO Person.
   * 
   * @since  COmanage Registry v4.3.4
   * @param  Array            $coProvisioningTargetData  CoProvisioningTargetData
   * @param  Array            $provisioningData          provisioning data
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function createAuthenticatedUser($coProvisioningTargetData, $provisioningData) {
    $authenticatedUser = array();
    $coPersonId = $provisioningData['CoPerson']['id'];
    $coProvisioningTargetId = $coProvisioningTargetData['CoDataverseProvisionerTarget']['co_provisioning_target_id'];

    $logPrefix = "createAuthenticatedUser: CO Person $coPersonId: ";

    // We only create authenticated users for active CO Person records.
    $status = $provisioningData['CoPerson']['status'];
    if($status != StatusEnum::Active) {
      $msg = "is not active so will not be provisioned";
      $this->log($logPrefix . $msg);
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
      $msg = "has no Dataverse identifier of type $identifierType so will not be provisioned";
      $this->log($logPrefix . $msg);
      return false;
    }

    // Skip over CO person records that are already provisioned.
    if(!empty($this->getAuthenticatedUserByIdentifier($dataverseIdentifier))) {
      $msg = "is already provisioned";
      $this->log($logPrefix . $msg);
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
      $msg = "has no persistent user ID of type $persistentUserIdType so will not be provisioned";
      $this->log($logPrefix . $msg);
      return false;
    }

    $authenticatedUser['persistentUserId'] = $persistentUserId;

    // Find the Name data.
    $nameType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['name_type'];
    $namei = null;

    foreach ($provisioningData['Name'] as $i => $name) {
      if($name['type'] == $nameType && (bool)$name['primary_name']) {
        $namei = $i;
        break;
      }
    }

    // We cannot provision without name data.
    if(is_null($namei)) {
      $msg = "has no name data so will not be provisioned";
      $this->log($logPrefix . $msg);
      return false;
    }

    $authenticatedUser['firstName'] = $provisioningData['Name'][$namei]['given'] ?? 'none';
    $authenticatedUser['lastName'] = $provisioningData['Name'][$namei]['family'] ?? 'none';

    // Find the EmailAddress data.
    $emailType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['email_type'];
    $emaili = null;

    foreach ($provisioningData['EmailAddress'] as $i => $email) {
      if($email['type'] == $emailType && empty($email['source_email_address_id'])) {
        $emaili = $i;
        break;
      }
    }

    // We cannot provision without email data.
    if(is_null($emaili)) {
      $msg = "has no email data so will not be provisioned";
      $this->log($logPrefix . $msg);
      return false;
    }

    $authenticatedUser['email'] = $provisioningData['EmailAddress'][$emaili]['mail'];
    $authenticatedUser['authenticationProviderId'] = $coProvisioningTargetData['CoDataverseProvisionerTarget']['authentication_provider_id'];

    // We only provision a user that is a member of at least one authorization
    // group, that is a CO Group with an Identifier of the configured type.
    $isAuthorized = false;
    $authGroupType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['group_type'];

    foreach ($provisioningData['CoGroupMember'] as $m) {
      $coGroupIdentifiers = $m['CoGroup']['Identifier'] ?? array();
      foreach ($coGroupIdentifiers as $i) {
        $gtype = $i['type'] ?? null;
        if($gtype == $coProvisioningTargetData['CoDataverseProvisionerTarget']['group_type']) {
          $isAuthorized = true;
        }
      }
    }

    if(!$isAuthorized) {
      $msg = "is not a member of authorization group with type $authGroupType so will not be created";
      $this->log($logPrefix . $msg);
      return false;
    }

    // Provision the authenticated user in Dataverse.
    $path = "/api/admin/authenticatedUsers?unblock-key=" . $this->unblockKey;
    $response = $this->Http->post($path, json_encode($authenticatedUser));

    if($response->code != 200) {
      $this->log($logPrefix . "Unable to create authenticated user " . print_r($authenticatedUser, true));
      $this->log($logPrefix . "Response from server was " . print_r($response, true));
      return false;
    }

    $msg = "created the dataverse authenticated user " . print_r($authenticatedUser, true);
    $this->log($logPrefix . $msg);

    // Record the Dataverse ID for the newly created authenticated user.
    $dataverseId = json_decode($response->body, true)['data']['id'];

    // Find any existing Identifier of type IdentifierEnum::ProvisioningTarget
    // and reconcile.
    $dataverseIdIdentifier = $this->getDataverseIdIdentifier($coPersonId, $coProvisioningTargetId);

    if(is_null($dataverseIdIdentifier)) {
      $this->addDataverseIdIdentifier($dataverseId, $coPersonId, $coProvisioningTargetId);
    } elseif ($dataverseIdIdentifier['Identifier']['id'] != $dataverseId) {
      $this->deleteDataverseIdIdentifier($dataverseIdIdentifier['Identifier']['id']);
      $this->addDataverseIdIdentifier($dataverseId, $coPersonId, $coProvisioningTargetId);
    }

    return true;
  }

  /**
   * Create HTTP client connected to DOI server
   *
   * @since  COmanage Registry v4.3.4
   * @param  Array $coProvisioningTargetData CO Provisioning Target data
   * @return Void
   * @throws InvalidArgumentException
   */

  protected function createDoiClient($coProvisioningTargetData) {
      $args = array();
      $args['conditions']['Server.id'] = $coProvisioningTargetData['CoDataverseProvisionerTarget']['doi_server_id'];
      $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = array('HttpServer');

      $CoProvisioningTarget = new CoProvisioningTarget();
      $srvr = $CoProvisioningTarget->Co->Server->find('first', $args);

      if(empty($srvr)) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.http_servers.1'), $coProvisioningTargetData['CoDataverseProvisionerTarget']['server_id'])));
      }
      
      $this->Doi = new CoHttpClient();
      
      $this->Doi->setConfig($srvr['HttpServer']);

      $this->Doi->setRequestOptions(array(
        'header' => array(
          'Accept'          => 'application/json',
          'Content-Type'    => 'application/json; charset=UTF-8'
        )
      ));
  }

  /**
   * Create a dataverse explicit group.
   *
   * @since  COmanage Registry v4.3.4
   * @param  Array                  $coProvisioningTargetData CO Provisioning Target data
   * @param  Array                  $provisioningData         Provisioning data, populated with ['CoGroup']
   * @return Boolean True on success
   */

  protected function createExplicitGroup($coProvisioningTargetData, $provisioningData) {
    $logPrefix = "createExplicitGroup: ";

    list($ownerDataverseAlias, $explicitGroupAlias, $comment) = array_values($this->coGroupToOwnerDataverse($coProvisioningTargetData, $provisioningData));

    if(is_null($ownerDataverseAlias) || is_null($explicitGroupAlias)) {
      $this->log($logPrefix . $comment);
      return false;
    }

    // Get the Dataverse explicit group.
    $dataverseExplicitGroup = $this->getDataverseExplicitGroup($ownerDataverseAlias, $explicitGroupAlias);

    if(!empty($dataverseExplicitGroup)) {
      $this->log($logPrefix . "Dataverse explicit group with alias $explicitGroupAlias already exists");
      return true;
    }

    // Provision the explicit group user in Dataverse.
    $dataverseExplicitGroup = array();
    $dataverseExplicitGroup["displayName"] = $provisioningData['CoGroup']['name'];
    $dataverseExplicitGroup["description"] = $provisioningData['CoGroup']['description'];
    $dataverseExplicitGroup["aliasInOwner"] = $explicitGroupAlias;

    $path = "/api/dataverses/" . $ownerDataverseAlias . "/groups" . "?unblock-key=" . $this->unblockKey;
    $response = $this->Http->post($path, json_encode($dataverseExplicitGroup));

    if($response->code != 201) {
      $this->log($logPrefix . "Error creating explicit group " . print_r($dataverseExplicitGroup, true));
      $this->log($logPrefix . "Response from server was " . print_r($response, true));
      return false;
    }

    return true;
  }
  
  /**
   * Create HTTP client connected to Dataverse server
   *
   * @since   COmanage Registry v4.3.4
   * @param   Array $coProvisioningTargetData CO Provisioning target data
   * @return  Void
   * @throws  InvalidArgumentException
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
   * Map DOI to a dataverse server host.
   *
   * @since  COmanage Registry v4.3.4
   * @param  String                 $doi                      DOI
   * @param  Array                  $coProvisioningTargetData CO Provisioning Target data
   * @return String dataserver server host
   */

  protected function doiToMappedServerHost($doi, $coProvisioningTargetData) {
    $logPrefix = "doiToMappedServerHost: DOI $doi: ";

    $host = null;
    $this->createDoiClient($coProvisioningTargetData);

    $path = "/api/handles/" . $doi;
    $response = $this->Doi->get($path);

    if($response->code == 200) {
      $values = json_decode($response->body, true)['values'] ?? null;
      foreach($values as $v) {
        if($v['type'] == 'URL') {
          $citationUrl = $v['data']['value'];
          $host = parse_url($citationUrl)['host'];
          $msg = "citation URL $citationUrl mapped to host $host";
          $this->log($logPrefix . $msg);
        }
      }
    } else {
      $msg = "DOI server return code was " . $response->code . " could not determine host";
      $this->log($logPrefix . $msg);
    }

    return $host;
  }

  /**
   * Map DOI to an owner dataverse alias.
   *
   * @since  COmanage Registry v4.3.4
   * @param  String $doi DOI
   * @return String owner dataverse alias
   */

  protected function doiToOwnerDataverseAlias($doi) {
    $logPrefix = "doiToOwnerDataverseAlias: DOI $doi: ";

    $ownerDataverseAlias = null;

    $path = "/api/datasets/:persistentId/";

    $query = array();
    $query['persistentId'] = "doi:" . $doi;
    $query['returnOwners'] = "true";
    $query['unblock-key'] = $this->unblockKey;

    $response = $this->Http->get($path, $query);

    if($response->code == 200) {
      $body = json_decode($response->body, true);
      $type = $body['data']['isPartOf']['type'] ?? null;
      $identifier = $body['data']['isPartOf']['identifier'] ?? null;

      if($type == "DATAVERSE" && !empty($identifier)) {
        $ownerDataverseAlias = $identifier;
        $msg = "owner dataverse alias is $ownerDataverseAlias";
        $this->log($logPrefix . $msg);
      }
    } else {
      $msg = "DOI return code was " . $response->code . " could not determine owner dataverse alias";
      $this->log($logPrefix . $msg);
    }

    return $ownerDataverseAlias;
  }

  /**
   * Get Dataverse authenticated user using Dataverse identifier
   *
   * @since  COmanage Registry v4.3.4
   * @param  String  $identifier  Dataverse identifier
   * @return Array   Array of authenticated user details 
   * @throws RuntimeException
   */

  protected function getAuthenticatedUserByIdentifier($identifier) {
    $logPrefix = "getAuthenticatedUserByIdentifier: identifier $identifier: ";
    $authenticatedUser = array();

    $path = "/api/admin/authenticatedUsers/" . $identifier;
    $query = array('unblock-key' => $this->unblockKey);
    $response = $this->Http->get($path, $query);

    if($response->code == 200) {
      $authenticatedUser = json_decode($response->body, true)['data'];
    } else {
      $msg = "Dataverse server return code was " . $response->code;
      $this->log($logPrefix . $msg);
    }

    return $authenticatedUser;
  }


  /**
   * Get a dataverse explicit group.
   *
   * @since  COmanage Registry v4.3.4
   * @param  String $ownerDataverseAlias owner dataverse alias 
   * @param  String $explicitGroupAlias  explicit group alias
   * @return Array explicit group object
   */

  protected function getDataverseExplicitGroup($ownerDataverseAlias, $explicitGroupAlias) {
    $logPrefix = "getDataverseExplicitGroup: owner dataverse alias $ownerDataverseAlias: explicit group alias $explicitGroupAlias: ";
    $dataverseExplicitGroup = array();

    $path = "/api/dataverses/$ownerDataverseAlias/groups/$explicitGroupAlias";
    $query = array('unblock-key' => $this->unblockKey);
    $response = $this->Http->get($path, $query);

    if($response->code == 200) {
      $dataverseExplicitGroup = json_decode($response->body, true)['data'];
    } else {
      $msg = "Dataverse server return code was " . $response->code;
      $this->log($logPrefix . $msg);
    }

    return $dataverseExplicitGroup;
  }

  /**
   * Get Dataverse ID Identifier of type IdentifierEnum::ProvisioningTarget
   *
   * @since  COmanage Registry v4.3.4
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
   * Log output from this provisioner.
   *
   * @since COmanage Registry v4.3.5.
   * @return bool Success of log write.
   */

  public function log($msg, $type = LOG_ERR, $scope = null) {
    $prefix = "CoDataverseProvisionerTarget ID " . $this->activeId . ": ";

    return parent::log($prefix . $msg, $type, $scope);
  }

  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v4.3.4
   * @param  Array                  $coProvisioningTargetData CO Provisioning Target data
   * @param  ProvisioningActionEnum $op                       Registry transaction type triggering provisioning
   * @param  Array                  $provisioningData         Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    // Set the ID for this instance for logging.
    $this->activeId = $coProvisioningTargetData['CoDataverseProvisionerTarget']['id'];

    // Initialize HTTP client connection to Dataverse server.
    $this->createHttpClient($coProvisioningTargetData);

    switch($op) {
      // We only write users to the Dataverse server once but the user must also be
      // a member of at least one authorization group, identified by having the configured
      // Identifier type. Since after enrollment/onboarding the user may not be a member
      // of the authorization group yet, we do take action on CoPersonUpdated to catch
      // the change in membership.
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUpdated:
        $ret = $this->createAuthenticatedUser($coProvisioningTargetData, $provisioningData);
        break;
      // We only write explicit groups to the Dataverse server once for now.
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        $ret = $this->createExplicitGroup($coProvisioningTargetData, $provisioningData);
        break;
      // We update explicit group memberships. Updates on CO Group name or description
      // are currently not supported.
      case ProvisioningActionEnum::CoGroupUpdated:
        $ret = $this->updateExplicitGroupMembership($coProvisioningTargetData, $provisioningData);
        break;
      default:
        // Ignore all other actions.
        $ret = true;
        break;
    }

    return $ret;
  }

  /**
   * Determine the provisioning status of this target.
   *
   * @since  COmanage Registry v4.3.4
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

    // Set logging details.
    $this->activeId = $coProvisioningTargetData['CoDataverseProvisionerTarget']['id'];
    $logPrefix = "status: CO Person $id: ";

    // Create HTTP client to connect to Dataverse server.
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
        $msg = "No Identifier of type " . $identifierType . " for CO Person";
        $ret['comment'] = $msg;
        $this->log($logPrefix . $msg);
        return $ret;
      }

      // Query Dataverse server for authenticated user object.
      $authenticatedUser = $this->getAuthenticatedUserByIdentifier($dataverseIdentifier);

      if(!empty($authenticatedUser)) {
        // The user is provisioned.
        $ret['status'] = ProvisioningStatusEnum::Provisioned;
        $ret['comment'] = $authenticatedUser['deactivated'] ? "User is deactivated in Dataverse" : "User is active in Dataverse";
        $ret['timestamp'] = $authenticatedUser['createdTime'];

        $this->log($logPrefix . $ret['comment']);
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
    } else if ($model->name == 'CoGroup') {
      // Pull the Co Group record.
      $args = array();
      $args['conditions']['CoGroup.id'] = $id;
      $args['contain'] = array();
      $args['contain'][] = 'Identifier';

      $coGroup = $this->CoProvisioningTarget->Co->CoGroup->find('first', $args);

      // Find the owner dataverse and explicit group alias.
      $obj = $this->coGroupToOwnerDataverse($coProvisioningTargetData, $coGroup);
      list($ownerDataverseAlias, $explicitGroupAlias, $comment) = array_values($obj);

      if(is_null($ownerDataverseAlias) || is_null($explicitGroupAlias)) {
        $ret['comment'] = $comment;
        return $ret;
      }

      // Try to get the Dataverse explicit group.
      $dataverseExplicitGroup = $this->getDataverseExplicitGroup($ownerDataverseAlias, $explicitGroupAlias);

      if(!empty($dataverseExplicitGroup)) {
        $ret['status'] = ProvisioningStatusEnum::Provisioned;
        $ret['comment'] = "Owner dataverse alias is $ownerDataverseAlias";
      }
    }

    return $ret;
  }

  /**
   * Update memberships in the dataverse explicit group.
   *
   * @since  COmanage Registry v4.3.4
   * @param  Array $coProvisioningTargetData CO Provisioning Target data
   * @param  Array $provisioningData         Provisioning data, populated with ['CoPerson'] and ['CoGroup']
   * @return Boolean True on success
   */
  
  protected function updateExplicitGroupMembership($coProvisioningTargetData, $provisioningData) {
    // We only operate on CO Group updates that include membership updates.
    $coPersonId = $provisioningData['CoGroup']['CoPerson']['id'] ?? null;
    if(is_null($coPersonId)) {
      return false;
    }

    $logPrefix = "updateExplicitGroupMembership: CO Person $coPersonId: ";

    // Pull the CoPerson record to find the Dataverse Identifier.
    $args = array();
    $args['conditions']['CoPerson.id'] = $coPersonId;
    $args['contain'] = array();
    $args['contain'][] = 'Identifier';

    $coPerson = $this->CoProvisioningTarget->Co->CoPerson->find('first', $args);

    // Find the Dataverse identifier.
    $identifierType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['identifier_type'];
    $dataverseIdentifier = null;
    foreach($coPerson['Identifier'] as $identifier) {
      if($identifier['type'] == $identifierType && $identifier['status'] = SuspendableStatusEnum::Active) {
        $dataverseIdentifier = $identifier['identifier'];
        break;
      }
    }

    if(is_null($dataverseIdentifier)) {
      $msg = "Could not determine dataverse Identifier";
      $this->log($logPrefix . $msg);
      return false;
    }

    // Find the owner dataverse and explicit group alias.
    $obj = $this->coGroupToOwnerDataverse($coProvisioningTargetData, $provisioningData);
    list($ownerDataverseAlias, $explicitGroupAlias, $comment) = array_values($obj);

    if(is_null($ownerDataverseAlias) || is_null($explicitGroupAlias)) {
      $msg = "Could not map CO Group to owner dataverse and explicit group alias";
      $this->log($logPrefix . $msg);
      return false;
    }

    // The URL path is the same for adding (PUT) or removing (DELETE).
    $path = "/api/dataverses/$ownerDataverseAlias/groups/$explicitGroupAlias/roleAssignees/@$dataverseIdentifier";
    $query = array('unblock-key' => $this->unblockKey);

    if(!empty($provisioningData['CoGroup']['CoGroupMember'])) {
      $response = $this->Http->put($path, $query);
      if($response->code != 200) {
        $this->log($logPrefix . "Error adding membership");
        $this->log($logPrefix . "Response from server was " . print_r($response, true));
        return false;
      }
      $msg = "added to group alias $explicitGroupAlias with owner dataverse alias $ownerDataverseAlias";
      $this->log($logPrefix . $msg);
    } else {
      $response = $this->Http->delete($path, $query);
      if($response->code != 200) {
        $this->log($logPrefix . "Error deleting membership");
        $this->log($logPrefix . "Response from server was " . print_r($response, true));
        return false;
      }
      $msg = "removed from group alias $explicitGroupAlias with owner dataverse alias $ownerDataverseAlias";
      $this->log($logPrefix . $msg);
    }

    return true;
  }
}
