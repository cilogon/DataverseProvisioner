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
App::uses("DataverseHttpClient", "DataverseProvisioner.Lib");
App::uses("DataverseOwnership", "DataverseProvisioner.Lib");

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
  
  // Instance of DataverseHttpClient for Dataverse server
  protected $Http = null;

  // Instance of CoHttpClient for DOI API server
  protected $Doi = null;

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
    'admin_token' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
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
   * Find Dataverse access that may sit on the wrong account, without
   * changing anything.
   *
   * For each CO Group carrying a DOI Identifier, report each member whose
   * Dataverse link is in conflict (including a username whose account is
   * not theirs), whether that account is in the Dataverse explicit group,
   * and each explicit group member not backed by a CO Group member's link.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Integer $id CO Dataverse Provisioner Target ID
   * @return Array   List of findings, each with kind, coGroupId, coGroupName, coPersonId, account, inGroup, comment
   * @throws InvalidArgumentException
   */

  public function audit($id) {
    $ret = array();

    $args = array();
    $args['conditions']['CoDataverseProvisionerTarget.id'] = $id;
    $args['contain'] = false;

    $coProvisioningTargetData = $this->find('first', $args);

    if(empty($coProvisioningTargetData)) {
      throw new InvalidArgumentException("No CO Dataverse Provisioner Target with ID $id");
    }

    $this->activeId = $id;
    $this->createHttpClient($coProvisioningTargetData);

    $cfg = $coProvisioningTargetData['CoDataverseProvisionerTarget'];
    $coId = $this->coIdForTarget($coProvisioningTargetData);

    $args = array();
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['contain'] = array('Identifier');

    $coGroups = $this->CoProvisioningTarget->Co->CoGroup->find('all', $args);

    $people = array();

    foreach($coGroups as $coGroup) {
      if(!$this->hasActiveGroupIdentifier($coGroup['Identifier'], $cfg['group_type'])) {
        continue;
      }

      $finding = array(
        'kind'        => null,
        'coGroupId'   => $coGroup['CoGroup']['id'],
        'coGroupName' => $coGroup['CoGroup']['name'],
        'coPersonId'  => null,
        'account'     => null,
        'inGroup'     => null,
        'comment'     => ''
      );

      $obj = $this->coGroupToOwnerDataverse($coProvisioningTargetData, $coGroup);
      list($ownerDataverseAlias, $explicitGroupAlias, $comment) = array_values($obj);

      if(is_null($ownerDataverseAlias) || is_null($explicitGroupAlias)) {
        $ret[] = array_merge($finding, array('kind' => 'error', 'comment' => $comment));
        continue;
      }

      $explicitGroup = $this->getDataverseExplicitGroup($ownerDataverseAlias, $explicitGroupAlias);
      $assignees = $explicitGroup['containedRoleAssignees'] ?? array();

      $args = array();
      $args['conditions']['CoGroupMember.co_group_id'] = $coGroup['CoGroup']['id'];
      $args['conditions']['CoGroupMember.member'] = true;
      $args['contain'] = false;

      $memberships = $this->CoProvisioningTarget->Co->CoGroup->CoGroupMember->find('all', $args);

      $backed = array();

      foreach($memberships as $m) {
        $coPersonId = $m['CoGroupMember']['co_person_id'];

        if(!isset($people[$coPersonId])) {
          $facts = $this->loadPersonFacts($coProvisioningTargetData, $coPersonId);

          $people[$coPersonId] = array('facts' => $facts, 'resolved' => null);

          if(!is_null($facts['username'])) {
            $people[$coPersonId]['resolved'] = $this->resolveLink($coProvisioningTargetData, $facts, $coPersonId);
          }
        }

        $facts = $people[$coPersonId]['facts'];
        $resolved = $people[$coPersonId]['resolved'];

        if(is_null($resolved)) {
          continue;
        }

        if($resolved['state'] == 'trusted' || $resolved['state'] == 'prefix') {
          $backed[] = '@' . DataverseOwnership::username($resolved['account']);
        } elseif($resolved['state'] == 'conflict' || $resolved['state'] == 'error') {
          $suspect = '@' . $facts['username'];

          $ret[] = array_merge($finding, array(
            'kind'       => 'person',
            'coPersonId' => $coPersonId,
            'account'    => $suspect,
            'inGroup'    => in_array($suspect, $assignees),
            'comment'    => $resolved['comment']
          ));
        }
      }

      foreach($assignees as $a) {
        if(is_string($a) && str_starts_with($a, '@') && !in_array($a, $backed)) {
          $ret[] = array_merge($finding, array(
            'kind'    => 'unexpected',
            'account' => $a,
            'inGroup' => true,
            'comment' => "Dataverse group member not backed by a CO Group member with a trusted link"
          ));
        }
      }
    }

    return $ret;
  }

  /**
   * Map CO Group to owner dataverse and explicit group alias combination
   *
   * The CO Group Identifier of the configured type holds the DOI, optionally
   * followed by "-<suffix>" naming an access variant. The full identifier is
   * tried as a DOI first; the suffix is stripped only when that lookup
   * definitely finds nothing. Any other lookup failure stops the mapping.
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
    $rawIdentifier = null;
    $identifiers = array();

    if(!empty($coGroup['Identifier'])) {
      $identifiers = $coGroup['Identifier'];
    } elseif(!empty($coGroup['CoGroup']['Identifier'])) {
      $identifiers = $coGroup['CoGroup']['Identifier'];
    }

    foreach($identifiers as $identifier) {
      if($identifier['type'] == $groupIdentifierType && $identifier['status'] == SuspendableStatusEnum::Active) {
        $rawIdentifier = $identifier['identifier'];
        break;
      }
    }

    if(is_null($rawIdentifier)) {
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

    foreach(DataverseOwnership::doiInterpretations($rawIdentifier) as $candidate) {
      $doi = $candidate['doi'];

      if(!$skipDoiMapping) {
        // Exchange the DOI using the DOI API for a server host.
        $mapped = $this->doiToMappedServerHost($doi, $coProvisioningTargetData);

        if($mapped['status'] == 'notfound') {
          // Not a DOI as written, so try the next interpretation.
          continue;
        }

        if($mapped['status'] != 'found') {
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
        if($myServerHost != $mapped['host']) {
          $ret['comment'] = "DOI does not map to this Dataverse server";
          return $ret;
        }
      }

      // Query the Dataverse server with the DOI persistent ID to find the owner dataverse.
      $owner = $this->doiToOwnerDataverseAlias($doi);

      if($owner['status'] == 'notfound' && $skipDoiMapping) {
        // Without the DOI API the dataset lookup decides which interpretation is real.
        continue;
      }

      if($owner['status'] != 'found') {
        $ret['comment'] = "Unable to determine owner dataverse for DOI $doi";
        return $ret;
      }

      $ret['ownerDataverseAlias'] = $owner['alias'];

      // The group alias in the owning dataverse/collection is constructed from the DOI.
      $alias = "authorized_" . str_replace(array(".", "/"), array("_", "_"), $doi);

      if(!empty($candidate['suffix'])) {
        $sanitizedSuffix = strtolower(preg_replace('/[^A-Za-z0-9_]/', '_', $candidate['suffix']));

        if(strlen($sanitizedSuffix) > 0) {
          $alias = $alias . '_' . $sanitizedSuffix;
        }
      }

      $ret['explicitGroupAlias'] = $alias;

      return $ret;
    }

    $ret['comment'] = "Unable to map DOI to Dataverse server";
    return $ret;
  }

  /**
   * Find the CO of a provisioning target.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array   $coProvisioningTargetData CO Provisioning Target data
   * @return Integer CO ID
   */

  protected function coIdForTarget($coProvisioningTargetData) {
    $coProvisioningTargetId = $coProvisioningTargetData['CoDataverseProvisionerTarget']['co_provisioning_target_id'];

    return $this->CoProvisioningTarget->field('co_id', array('CoProvisioningTarget.id' => $coProvisioningTargetId));
  }

  /**
   * Provision the Dataverse authenticated user for a CO Person.
   *
   * A Dataverse account is linked to the CO Person only when it is shown to
   * be theirs (see DataverseOwnership). Any doubt fails closed: no account is
   * created, no group membership is granted, and the reason is logged and
   * reported by status().
   *
   * @since  COmanage Registry v4.3.4
   * @param  Array            $coProvisioningTargetData  CoProvisioningTargetData
   * @param  Array            $provisioningData          provisioning data
   * @throws RuntimeException
   * @return boolean          true when the CO Person has a trusted link
   */
  
  protected function createAuthenticatedUser($coProvisioningTargetData, $provisioningData) {
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

    $facts = $this->personFacts($coProvisioningTargetData,
                                $provisioningData['Identifier'] ?? array(),
                                $provisioningData['EmailAddress'] ?? array());

    // We cannot provision an authenticated user without a Dataverse identifier.
    $identifierType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['identifier_type'];
    if(is_null($facts['username'])) {
      $msg = "has no Dataverse identifier of type $identifierType so will not be provisioned";
      $this->log($logPrefix . $msg);
      return false;
    }

    // We cannot provision an authenticated user without a persistent user ID.
    $persistentUserIdType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['persistent_user_id_type'];
    if(is_null($facts['persistentUserId'])) {
      $msg = "has no persistent user ID of type $persistentUserIdType so will not be provisioned";
      $this->log($logPrefix . $msg);
      return false;
    }

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

    // We cannot provision without email data.
    if(is_null($facts['email'])) {
      $msg = "has no email data so will not be provisioned";
      $this->log($logPrefix . $msg);
      return false;
    }

    // We only provision a user that is a member of at least one authorization
    // group, that is a CO Group with an Identifier of the configured type.
    $isAuthorized = false;
    $authGroupType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['group_type'];

    foreach ($provisioningData['CoGroupMember'] as $m) {
      $coGroupIdentifiers = $m['CoGroup']['Identifier'] ?? array();
      foreach ($coGroupIdentifiers as $i) {
        $gtype = $i['type'] ?? null;
        if($gtype == $authGroupType) {
          $isAuthorized = true;
        }
      }
    }

    if(!$isAuthorized) {
      $msg = "is not a member of authorization group with type $authGroupType so will not be created";
      $this->log($logPrefix . $msg);
      return false;
    }

    $linkRow = $this->getDataverseIdIdentifier($coPersonId, $coProvisioningTargetId);
    $link = DataverseOwnership::parseLink($linkRow['Identifier']['identifier'] ?? null, $coProvisioningTargetId);

    $atUsername = $this->lookupUserByUsername($facts['username']);
    if($atUsername['error']) {
      $this->log($logPrefix . "unable to query Dataverse for username " . $facts['username']);
      return false;
    }

    $account = null;

    if($link['state'] == DataverseOwnership::LINK_TRUSTED) {
      if(!$atUsername['found'] || $atUsername['user']['id'] != $link['id']) {
        $msg = "conflict: linked Dataverse account " . $link['id'] . " is no longer at username " . $facts['username'];
        $this->log($logPrefix . $msg);
        return false;
      }

      $account = $atUsername['user'];
    } elseif($link['state'] == DataverseOwnership::LINK_PREFIX) {
      if($atUsername['found']
         && $atUsername['user']['id'] == $link['id']
         && DataverseOwnership::ownsAccount($facts, $atUsername['user'])) {
        // The link made before ownership checks passes them, so trust it from now on.
        if(!$this->saveLink($coPersonId, $coProvisioningTargetId, $link['id'], $linkRow)) {
          return false;
        }

        $account = $atUsername['user'];
        $this->log($logPrefix . "verified link to Dataverse account " . $link['id'] . " made before ownership checks");
      } else {
        // Set the failed link aside and look for an account that is theirs.
        $this->log($logPrefix . "link to Dataverse account " . $link['id'] . " made before ownership checks fails them");
      }
    }

    if(is_null($account)) {
      $account = $this->linkOwnedAccount($coProvisioningTargetData, $provisioningData, $facts, $atUsername, $linkRow);

      if(is_null($account)) {
        return false;
      }
    }

    if(!empty($account['deactivated'])) {
      $this->log($logPrefix . "Dataverse account " . $account['id'] . " is deactivated so no group memberships will be granted");
      return true;
    }

    // Grant any group memberships that were skipped while the CO Person had no trusted link.
    $username = DataverseOwnership::username($account);

    foreach ($provisioningData['CoGroupMember'] as $m) {
      if(empty($m['member']) || empty($m['CoGroup'])) {
        continue;
      }

      if($this->hasActiveGroupIdentifier($m['CoGroup']['Identifier'] ?? array(), $authGroupType)) {
        $coGroup = array(
          'CoGroup'    => $m['CoGroup'],
          'Identifier' => $m['CoGroup']['Identifier']
        );

        $this->grantGroupMembership($coProvisioningTargetData, $coGroup, $username, $logPrefix);
      }
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
   * @param  Array                  $mapping                  Result of coGroupToOwnerDataverse() when already known
   * @return Boolean True on success
   */

  protected function createExplicitGroup($coProvisioningTargetData, $provisioningData, $mapping = null) {
    $logPrefix = "createExplicitGroup: ";

    if(is_null($mapping)) {
      $mapping = $this->coGroupToOwnerDataverse($coProvisioningTargetData, $provisioningData);
    }

    list($ownerDataverseAlias, $explicitGroupAlias, $comment) = array_values($mapping);

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

    $path = "/api/dataverses/" . $ownerDataverseAlias . "/groups";
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

      $config = $srvr['HttpServer'];
      $apiToken = $config['password'];
      $adminToken = $coProvisioningTargetData['CoDataverseProvisionerTarget']['admin_token'];
      $this->Http = new DataverseHttpClient($config, $apiToken, $adminToken);
  }

  /**
   * Map DOI to a dataverse server host.
   *
   * @since  COmanage Registry v4.3.4
   * @param  String                 $doi                      DOI
   * @param  Array                  $coProvisioningTargetData CO Provisioning Target data
   * @return Array status (found, notfound, or error) and host
   */

  protected function doiToMappedServerHost($doi, $coProvisioningTargetData) {
    $logPrefix = "doiToMappedServerHost: DOI $doi: ";

    $ret = array('status' => 'error', 'host' => null);
    $this->createDoiClient($coProvisioningTargetData);

    $path = "/api/handles/" . $doi;
    $response = $this->Doi->get($path);

    if($response->code == 200) {
      $values = json_decode($response->body, true)['values'] ?? array();
      foreach($values as $v) {
        if($v['type'] == 'URL') {
          $citationUrl = $v['data']['value'];
          $ret['host'] = parse_url($citationUrl)['host'] ?? null;
          $msg = "citation URL $citationUrl mapped to host " . $ret['host'];
          $this->log($logPrefix . $msg);
        }
      }

      if(!empty($ret['host'])) {
        $ret['status'] = 'found';
      } else {
        $this->log($logPrefix . "DOI server returned no URL so could not determine host");
      }
    } elseif($response->code == 404) {
      $ret['status'] = 'notfound';
      $this->log($logPrefix . "DOI server does not know this DOI");
    } else {
      $msg = "DOI server return code was " . $response->code . " could not determine host";
      $this->log($logPrefix . $msg);
    }

    return $ret;
  }

  /**
   * Map DOI to an owner dataverse alias.
   *
   * @since  COmanage Registry v4.3.4
   * @param  String $doi DOI
   * @return Array status (found, notfound, or error) and owner dataverse alias
   */

  protected function doiToOwnerDataverseAlias($doi) {
    $logPrefix = "doiToOwnerDataverseAlias: DOI $doi: ";

    $ret = array('status' => 'error', 'alias' => null);

    $path = "/api/datasets/:persistentId/";

    $query = array();
    // Check if the DOI starts with the "doi:" prefix and add it if not.
    if (str_starts_with($doi, "doi:")) {
      $query['persistentId'] = $doi;
    } else {
      $query['persistentId'] = "doi:" . $doi;
    }    
    $query['returnOwners'] = "true";

    $response = $this->Http->get($path, $query);

    if($response->code == 200) {
      $body = json_decode($response->body, true);
      $type = $body['data']['isPartOf']['type'] ?? null;
      $identifier = $body['data']['isPartOf']['identifier'] ?? null;

      if($type == "DATAVERSE" && !empty($identifier)) {
        $ret['status'] = 'found';
        $ret['alias'] = $identifier;
        $msg = "owner dataverse alias is $identifier";
        $this->log($logPrefix . $msg);
      }
    } elseif($response->code == 404) {
      $ret['status'] = 'notfound';
      $this->log($logPrefix . "no dataset with this DOI");
    } else {
      $msg = "DOI return code was " . $response->code . " could not determine owner dataverse alias";
      $this->log($logPrefix . $msg);
    }

    return $ret;
  }

  /**
   * Find Dataverse authenticated users whose email exactly matches.
   *
   * The list-users search matches prefixes and does not return the login
   * identity, so each exact match is fetched again by its username.
   *
   * @since  COmanage Registry v4.3.5
   * @param  String  $email  email address
   * @return Array   error flag and list of authenticated users
   */

  protected function findUsersByEmail($email) {
    $logPrefix = "findUsersByEmail: email $email: ";
    $ret = array('error' => false, 'accounts' => array());

    if(empty($email)) {
      return $ret;
    }

    $path = "/api/admin/list-users";

    $query = array();
    $query['searchTerm'] = $email;

    $response = $this->Http->get($path, $query);

    if($response->code != 200) {
      $msg = "Dataverse server return code was " . $response->code;
      $this->log($logPrefix . $msg);
      $ret['error'] = true;
      return $ret;
    }

    $users = json_decode($response->body, true)['data']['users'] ?? array();

    foreach($users as $u) {
      if(!DataverseOwnership::emailsMatch($email, $u['email'] ?? null)) {
        continue;
      }

      $lookup = $this->lookupUserByUsername(DataverseOwnership::username($u));

      if($lookup['error']) {
        $ret['error'] = true;
        return $ret;
      }

      if($lookup['found']) {
        $ret['accounts'][] = $lookup['user'];
      }
    }

    return $ret;
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
    $response = $this->Http->get($path);

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
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;

    $dataverseIdIdentifier = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);

    if(!empty($dataverseIdIdentifier)) {
      return $dataverseIdIdentifier;
    } else {
      return null;
    }
  }

  /**
   * Grant a Dataverse explicit group membership for a CO Group.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array   $coProvisioningTargetData CO Provisioning Target data
   * @param  Array   $coGroup                  CO Group data with ['CoGroup'] and ['Identifier']
   * @param  String  $username                 Dataverse username of the linked account
   * @param  String  $logPrefix                Log prefix of the caller
   * @return Boolean True on success
   */

  protected function grantGroupMembership($coProvisioningTargetData, $coGroup, $username, $logPrefix) {
    $obj = $this->coGroupToOwnerDataverse($coProvisioningTargetData, $coGroup);
    list($ownerDataverseAlias, $explicitGroupAlias, $comment) = array_values($obj);

    if(is_null($ownerDataverseAlias) || is_null($explicitGroupAlias)) {
      $this->log($logPrefix . "skipping CO Group " . ($coGroup['CoGroup']['id'] ?? '?') . ": " . $comment);
      return false;
    }

    if(!$this->createExplicitGroup($coProvisioningTargetData, $coGroup, $obj)) {
      return false;
    }

    $path = "/api/dataverses/$ownerDataverseAlias/groups/$explicitGroupAlias/roleAssignees/@$username";
    $response = $this->Http->put($path);

    if($response->code != 200) {
      $msg = ($response->code == 403) ? "Dataverse refused membership for @$username (unknown or deactivated account)" : "Error adding membership";
      $this->log($logPrefix . $msg);
      $this->log($logPrefix . "Response from server was " . print_r($response, true));
      return false;
    }

    $this->log($logPrefix . "added @$username to group alias $explicitGroupAlias with owner dataverse alias $ownerDataverseAlias");
    return true;
  }

  /**
   * Determine whether a CO Group carries an Active Identifier of the given type.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array   $identifiers CO Group Identifiers
   * @param  String  $type        Identifier type
   * @return Boolean True if such an Identifier is present
   */

  protected function hasActiveGroupIdentifier($identifiers, $type) {
    foreach($identifiers as $i) {
      if(($i['type'] ?? null) == $type && ($i['status'] ?? null) == SuspendableStatusEnum::Active) {
        return true;
      }
    }

    return false;
  }

  /**
   * Link a CO Person to the Dataverse account that is theirs, creating it if
   * their username is free. Every doubt is a logged conflict.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array  $coProvisioningTargetData CO Provisioning Target data
   * @param  Array  $provisioningData         CO Person provisioning data
   * @param  Array  $facts                    CO Person facts from personFacts()
   * @param  Array  $atUsername               Result of lookupUserByUsername() for the CO Person's username
   * @param  Array  $linkRow                  Existing link Identifier, or null
   * @return Array  Linked Dataverse account, or null on conflict or error
   */

  protected function linkOwnedAccount($coProvisioningTargetData, $provisioningData, $facts, $atUsername, $linkRow) {
    $coPersonId = $provisioningData['CoPerson']['id'];
    $coProvisioningTargetId = $coProvisioningTargetData['CoDataverseProvisionerTarget']['co_provisioning_target_id'];
    $logPrefix = "linkOwnedAccount: CO Person $coPersonId: ";

    $byEmail = $this->findUsersByEmail($facts['email']);
    if($byEmail['error']) {
      $this->log($logPrefix . "unable to query Dataverse for email " . $facts['email']);
      return null;
    }

    $decision = DataverseOwnership::classify($facts,
                                             $atUsername['found'] ? $atUsername['user'] : null,
                                             $byEmail['accounts']);

    if($decision['skippedUnverifiedEmail']) {
      $this->log($logPrefix . "email " . $facts['email'] . " is not verified in Registry so it was not used to match a Dataverse account");
    }

    if($decision['outcome'] == DataverseOwnership::OUTCOME_CONFLICT) {
      $this->log($logPrefix . "conflict: Dataverse username " . $decision['conflictUsername'] . " belongs to another person's account");
      return null;
    }

    if($decision['outcome'] == DataverseOwnership::OUTCOME_OWNED) {
      $account = $decision['account'];
      $username = DataverseOwnership::username($account);

      // A Dataverse account may be linked to only one CO Person.
      $holders = $this->linkHolders($coProvisioningTargetId, $account['id'], $coPersonId);

      if(!empty($holders['trusted'])) {
        $this->log($logPrefix . "conflict: Dataverse account @$username is already linked to CO Person " . implode(", ", $holders['trusted']));
        return null;
      }

      if(!empty($holders['prefix'])) {
        $this->log($logPrefix . "suspect: CO Person " . implode(", ", $holders['prefix']) . " has a link to Dataverse account @$username made before ownership checks");
      }

      if(!$this->syncUsername($coProvisioningTargetData, $facts, $username, $logPrefix)) {
        return null;
      }

      if(!$this->saveLink($coPersonId, $coProvisioningTargetId, $account['id'], $linkRow)) {
        return null;
      }

      $this->log($logPrefix . "linked Dataverse account @$username by " . $decision['rule']);
      return $account;
    }

    // No account is theirs. Creating one with an email already in use fails, so report that instead.
    if($decision['skippedUnverifiedEmail']) {
      $this->log($logPrefix . "conflict: email " . $facts['email'] . " is already used by a Dataverse account and is not verified in Registry");
      return null;
    }

    $nameType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['name_type'];
    $name = array();
    foreach ($provisioningData['Name'] as $n) {
      if($n['type'] == $nameType && (bool)$n['primary_name']) {
        $name = $n;
        break;
      }
    }

    $authenticatedUser = array();
    $authenticatedUser['identifier'] = $facts['username'];
    $authenticatedUser['persistentUserId'] = $facts['persistentUserId'];
    $authenticatedUser['firstName'] = $name['given'] ?? 'none';
    $authenticatedUser['lastName'] = $name['family'] ?? 'none';
    $authenticatedUser['email'] = $facts['email'];
    $authenticatedUser['authenticationProviderId'] = $facts['providerId'];

    $path = "/api/admin/authenticatedUsers";
    $response = $this->Http->post($path, json_encode($authenticatedUser));

    if($response->code != 200 && $response->code != 201) {
      $msg = "conflict: unable to create the dataverse authenticated user (the email or login identity may already be used by another account) " . print_r($authenticatedUser, true);
      $this->log($logPrefix . $msg);
      return null;
    }

    $account = json_decode($response->body, true)['data'] ?? array();

    if(empty($account['id'])) {
      $this->log($logPrefix . "Dataverse did not return the created account");
      return null;
    }

    $username = DataverseOwnership::username($account) ?? $facts['username'];
    $this->log($logPrefix . "created the dataverse authenticated user @$username");

    // Dataverse renames a username that was taken in the meantime.
    if(!$this->syncUsername($coProvisioningTargetData, $facts, $username, $logPrefix)) {
      return null;
    }

    if(!$this->saveLink($coPersonId, $coProvisioningTargetId, $account['id'], $linkRow)) {
      return null;
    }

    return $account;
  }

  /**
   * Find the CO Persons holding a link to a Dataverse account.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Integer $coProvisioningTargetId Provisioning Target ID
   * @param  Integer $dataverseId            Dataverse account id
   * @param  Integer $excludeCoPersonId      CO Person to leave out
   * @return Array   CO Person ids with trusted links and with links made before ownership checks
   */

  protected function linkHolders($coProvisioningTargetId, $dataverseId, $excludeCoPersonId) {
    $ret = array('trusted' => array(), 'prefix' => array());

    $args = array();
    $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
    $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['Identifier.identifier'] = array(
      DataverseOwnership::formatLink($coProvisioningTargetId, $dataverseId),
      DataverseOwnership::formatPrefixLink($coProvisioningTargetId, $dataverseId)
    );
    $args['conditions']['Identifier.co_person_id !='] = $excludeCoPersonId;
    $args['contain'] = false;

    $rows = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('all', $args);

    foreach($rows as $r) {
      $link = DataverseOwnership::parseLink($r['Identifier']['identifier'], $coProvisioningTargetId);
      $ret[$link['state'] == DataverseOwnership::LINK_TRUSTED ? 'trusted' : 'prefix'][] = $r['Identifier']['co_person_id'];
    }

    return $ret;
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
   * Load a CO Person and gather the attributes the ownership rules use.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array   $coProvisioningTargetData CO Provisioning Target data
   * @param  Integer $coPersonId               CO Person ID
   * @return Array   CO Person facts from personFacts()
   */

  protected function loadPersonFacts($coProvisioningTargetData, $coPersonId) {
    $args = array();
    $args['conditions']['CoPerson.id'] = $coPersonId;
    $args['contain'] = array('Identifier', 'EmailAddress');

    $coPerson = $this->CoProvisioningTarget->Co->CoPerson->find('first', $args);

    return $this->personFacts($coProvisioningTargetData,
                              $coPerson['Identifier'] ?? array(),
                              $coPerson['EmailAddress'] ?? array());
  }

  /**
   * Get Dataverse authenticated user using Dataverse username
   *
   * @since  COmanage Registry v4.3.5
   * @param  String  $username  Dataverse username, without the leading '@'
   * @return Array   found and error flags, and the authenticated user
   */

  protected function lookupUserByUsername($username) {
    $logPrefix = "lookupUserByUsername: username $username: ";
    $ret = array('found' => false, 'error' => false, 'user' => array());

    if(empty($username)) {
      return $ret;
    }

    $path = "/api/admin/authenticatedUsers/" . rawurlencode($username);
    $response = $this->Http->get($path);

    if($response->code == 200) {
      $ret['found'] = true;
      $ret['user'] = json_decode($response->body, true)['data'] ?? array();
    } elseif($response->code == 400 || $response->code == 404) {
      // Dataverse answers 400 for an unknown username.
    } else {
      $msg = "Dataverse server return code was " . $response->code;
      $this->log($logPrefix . $msg);
      $ret['error'] = true;
    }

    return $ret;
  }

  /**
   * Gather the CO Person attributes the ownership rules use.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array $coProvisioningTargetData CO Provisioning Target data
   * @param  Array $identifiers              CO Person Identifiers
   * @param  Array $emailAddresses           CO Person EmailAddresses
   * @return Array providerId, persistentUserId, email, emailVerified, username, usernameIdentifierId
   */

  protected function personFacts($coProvisioningTargetData, $identifiers, $emailAddresses) {
    $cfg = $coProvisioningTargetData['CoDataverseProvisionerTarget'];

    $facts = array(
      'providerId'           => $cfg['authentication_provider_id'],
      'persistentUserId'     => null,
      'email'                => null,
      'emailVerified'        => false,
      'username'             => null,
      'usernameIdentifierId' => null
    );

    foreach($identifiers as $identifier) {
      if(isset($identifier['status']) && $identifier['status'] != SuspendableStatusEnum::Active) {
        continue;
      }

      if($identifier['type'] == $cfg['identifier_type'] && is_null($facts['username'])) {
        $facts['username'] = $identifier['identifier'];
        $facts['usernameIdentifierId'] = $identifier['id'];
      }

      if($identifier['type'] == $cfg['persistent_user_id_type'] && is_null($facts['persistentUserId'])) {
        $facts['persistentUserId'] = $identifier['identifier'];
      }
    }

    foreach($emailAddresses as $email) {
      if($email['type'] == $cfg['email_type'] && empty($email['source_email_address_id'])) {
        $facts['email'] = $email['mail'];
        $facts['emailVerified'] = !empty($email['verified']);
        break;
      }
    }

    return $facts;
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
      case ProvisioningActionEnum::CoGroupAdded:
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
      case ProvisioningActionEnum::CoGroupUpdated:
        // Always try to create the explicit group since the method createExplicitGroup
        // will query the Dataverse server first to see if the group already exists
        // and take no action if it does exist.
        //
        // We do this because attaching the Identifier holding the DOI to the CO Group
        // does NOT invoke provisioning. So we rely on either the REST API call
        // doing another save to "update" the CO Group or the UI/UX doing it
        // or invoking a reprovision.
        $this->createExplicitGroup($coProvisioningTargetData, $provisioningData);
        // We update explicit group memberships. Updates on CO Group name or description
        // are currently not supported.
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
   * Resolve the CO Person's link to the Dataverse account it names, without
   * changing anything.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array   $coProvisioningTargetData CO Provisioning Target data
   * @param  Array   $facts                    CO Person facts from personFacts()
   * @param  Integer $coPersonId               CO Person ID
   * @return Array   state, account, link row, and a comment describing the state
   */

  protected function resolveLink($coProvisioningTargetData, $facts, $coPersonId) {
    $coProvisioningTargetId = $coProvisioningTargetData['CoDataverseProvisionerTarget']['co_provisioning_target_id'];

    $ret = array('state' => 'none', 'account' => null, 'linkRow' => null, 'comment' => '');

    $ret['linkRow'] = $this->getDataverseIdIdentifier($coPersonId, $coProvisioningTargetId);
    $link = DataverseOwnership::parseLink($ret['linkRow']['Identifier']['identifier'] ?? null, $coProvisioningTargetId);

    $atUsername = $this->lookupUserByUsername($facts['username']);

    if($atUsername['error']) {
      $ret['state'] = 'error';
      $ret['comment'] = "Unable to query Dataverse for username " . $facts['username'];
      return $ret;
    }

    $sameAccount = $atUsername['found'] && $atUsername['user']['id'] == $link['id'];

    switch($link['state']) {
      case DataverseOwnership::LINK_TRUSTED:
        if($sameAccount) {
          $ret['state'] = 'trusted';
          $ret['account'] = $atUsername['user'];
        } else {
          $ret['state'] = 'conflict';
          $ret['comment'] = "Conflict: linked Dataverse account " . $link['id'] . " is no longer at username " . $facts['username'];
        }
        break;
      case DataverseOwnership::LINK_PREFIX:
        if($sameAccount && DataverseOwnership::ownsAccount($facts, $atUsername['user'])) {
          $ret['state'] = 'prefix';
          $ret['account'] = $atUsername['user'];
          $ret['comment'] = "Not yet verified: link was made before ownership checks";
        } else {
          $ret['state'] = 'conflict';
          $ret['comment'] = "Conflict: link to Dataverse account " . $link['id'] . " made before ownership checks fails them";
        }
        break;
      default:
        if($atUsername['found'] && is_null(DataverseOwnership::ownsAccount($facts, $atUsername['user']))) {
          $ret['state'] = 'conflict';
          $ret['comment'] = "Conflict: Dataverse username " . $facts['username'] . " belongs to another person's account";
        } else {
          $ret['comment'] = "No Dataverse account is linked";
        }
        break;
    }

    return $ret;
  }

  /**
   * Save the link from a CO Person to their Dataverse account, without
   * triggering provisioning.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Integer $coPersonId             CO Person ID
   * @param  Integer $coProvisioningTargetId Provisioning Target ID
   * @param  Integer $dataverseId            Dataverse account id
   * @param  Array   $linkRow                Existing link Identifier to update in place, or null
   * @return Boolean True on success
   */

  protected function saveLink($coPersonId, $coProvisioningTargetId, $dataverseId, $linkRow) {
    $logPrefix = "saveLink: CO Person $coPersonId: ";
    $value = DataverseOwnership::formatLink($coProvisioningTargetId, $dataverseId);

    $Identifier = $this->CoProvisioningTarget->Co->CoPerson->Identifier;

    try {
      $Identifier->clear();

      if(!empty($linkRow['Identifier']['id'])) {
        $Identifier->id = $linkRow['Identifier']['id'];
        $ok = $Identifier->saveField('identifier', $value, array('provision' => false));
      } else {
        $args = array();
        $args['Identifier']['identifier'] = $value;
        $args['Identifier']['co_person_id'] = $coPersonId;
        $args['Identifier']['type'] = IdentifierEnum::ProvisioningTarget;
        $args['Identifier']['login'] = false;
        $args['Identifier']['status'] = SuspendableStatusEnum::Active;
        $args['Identifier']['co_provisioning_target_id'] = $coProvisioningTargetId;

        $ok = $Identifier->save($args, array('provision' => false));
      }
    }
    catch(Exception $e) {
      $this->log($logPrefix . "conflict: unable to save link $value: " . $e->getMessage());
      return false;
    }

    if(!$ok) {
      $this->log($logPrefix . "conflict: unable to save link $value");
      return false;
    }

    return true;
  }

  /**
   * Determine the provisioning status of this target.
   *
   * This only reads; it never changes links or triggers provisioning.
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

      $facts = $this->loadPersonFacts($coProvisioningTargetData, $id);

      if(is_null($facts['username'])) {
        $ret['comment'] = "No Identifier of type " . $identifierType . " for CO Person";
        return $ret;
      }

      $resolved = $this->resolveLink($coProvisioningTargetData, $facts, $id);

      switch($resolved['state']) {
        case 'trusted':
          $account = $resolved['account'];
          $ret['status'] = ProvisioningStatusEnum::Provisioned;
          $ret['comment'] = !empty($account['deactivated']) ? "User is deactivated in Dataverse" : "User is active in Dataverse";
          $ret['timestamp'] = $account['createdTime'] ?? null;
          break;
        case 'error':
          $ret['status'] = ProvisioningStatusEnum::Unknown;
          $ret['comment'] = $resolved['comment'];
          break;
        default:
          $ret['comment'] = $resolved['comment'];
          break;
      }

      return $ret;

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
        $displayName = $dataverseExplicitGroup['displayName'] ?? null;

        if(!is_null($displayName) && trim($displayName) === trim($coGroup['CoGroup']['name'])) {
          $ret['status'] = ProvisioningStatusEnum::Provisioned;
          $ret['comment'] = "Owner dataverse alias is $ownerDataverseAlias";
        } else {
          $ret['comment'] = "Explicit group alias $explicitGroupAlias exists but display name does not match Registry group";
        }
      }
    }

    return $ret;
  }

  /**
   * Set the CO Person's Dataverse username Identifier to the username of
   * their linked account, without triggering provisioning.
   *
   * @since  COmanage Registry v4.3.5
   * @param  Array   $coProvisioningTargetData CO Provisioning Target data
   * @param  Array   $facts                    CO Person facts from personFacts()
   * @param  String  $username                 Username of the linked Dataverse account
   * @param  String  $logPrefix                Log prefix of the caller
   * @return Boolean True when the Identifier holds $username
   */

  protected function syncUsername($coProvisioningTargetData, $facts, $username, $logPrefix) {
    if($username === $facts['username']) {
      return true;
    }

    $identifierType = $coProvisioningTargetData['CoDataverseProvisionerTarget']['identifier_type'];
    $coId = $this->coIdForTarget($coProvisioningTargetData);

    $Identifier = $this->CoProvisioningTarget->Co->CoPerson->Identifier;

    // Another CO Person holding this username would make the link ambiguous.
    $args = array();
    $args['conditions']['Identifier.type'] = $identifierType;
    $args['conditions']['Identifier.identifier'] = $username;
    $args['conditions']['Identifier.id !='] = $facts['usernameIdentifierId'];
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['contain'] = array('CoPerson');

    $holder = $Identifier->find('first', $args);

    if(!empty($holder)) {
      $this->log($logPrefix . "conflict: Dataverse username $username is the Registry identifier of CO Person " . $holder['Identifier']['co_person_id']);
      return false;
    }

    try {
      $Identifier->clear();
      $Identifier->id = $facts['usernameIdentifierId'];
      $ok = $Identifier->saveField('identifier', $username, array('provision' => false));
    }
    catch(Exception $e) {
      $this->log($logPrefix . "conflict: unable to change Identifier of type $identifierType from " . $facts['username'] . " to $username: " . $e->getMessage());
      return false;
    }

    if(!$ok) {
      $this->log($logPrefix . "conflict: unable to change Identifier of type $identifierType from " . $facts['username'] . " to $username");
      return false;
    }

    $this->log($logPrefix . "changed Identifier of type $identifierType from " . $facts['username'] . " to $username");
    return true;
  }

  /**
   * Update memberships in the dataverse explicit group.
   *
   * Changes are made only for the Dataverse account linked to the CO Person
   * by a trusted link. Without one, nothing is sent: a grant waits for the
   * CO Person to be linked, and a removal is skipped because the earlier
   * grant may sit on another person's account.
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
    $coProvisioningTargetId = $coProvisioningTargetData['CoDataverseProvisionerTarget']['co_provisioning_target_id'];

    $facts = $this->loadPersonFacts($coProvisioningTargetData, $coPersonId);

    if(is_null($facts['username'])) {
      $msg = "Could not determine dataverse Identifier";
      $this->log($logPrefix . $msg);
      return false;
    }

    // Is this a grant or a removal? Only a current membership row is a grant.
    $isMember = false;
    foreach($provisioningData['CoGroup']['CoGroupMember'] ?? array() as $m) {
      if(!empty($m['member'])
         && (empty($m['valid_from']) || strtotime($m['valid_from']) < time())
         && (empty($m['valid_through']) || strtotime($m['valid_through']) >= time())) {
        $isMember = true;
      }
    }

    $resolved = $this->resolveLink($coProvisioningTargetData, $facts, $coPersonId);

    if($resolved['state'] == 'prefix') {
      // The link made before ownership checks passes them, so trust it from now on.
      if(!$this->saveLink($coPersonId, $coProvisioningTargetId, $resolved['account']['id'], $resolved['linkRow'])) {
        return false;
      }
      $resolved['state'] = 'trusted';
    }

    if($resolved['state'] != 'trusted') {
      if($isMember) {
        $this->log($logPrefix . "no trusted link so no membership granted: " . $resolved['comment']);
      } else {
        $this->log($logPrefix . "no trusted link so removal skipped; an earlier grant may sit on Dataverse account @" . $facts['username'] . ": " . $resolved['comment']);
      }
      return false;
    }

    $account = $resolved['account'];
    $username = DataverseOwnership::username($account);

    if($isMember) {
      if(!empty($account['deactivated'])) {
        $this->log($logPrefix . "Dataverse account @$username is deactivated so no membership granted");
        return false;
      }

      return $this->grantGroupMembership($coProvisioningTargetData, $provisioningData, $username, $logPrefix);
    }

    // Find the owner dataverse and explicit group alias.
    $obj = $this->coGroupToOwnerDataverse($coProvisioningTargetData, $provisioningData);
    list($ownerDataverseAlias, $explicitGroupAlias, $comment) = array_values($obj);

    if(is_null($ownerDataverseAlias) || is_null($explicitGroupAlias)) {
      $msg = "Could not map CO Group to owner dataverse and explicit group alias";
      $this->log($logPrefix . $msg);
      return false;
    }

    $path = "/api/dataverses/$ownerDataverseAlias/groups/$explicitGroupAlias/roleAssignees/@$username";
    $response = $this->Http->delete($path);
    if($response->code != 200) {
      $this->log($logPrefix . "Error deleting membership");
      $this->log($logPrefix . "Response from server was " . print_r($response, true));
      return false;
    }

    $msg = "removed @$username from group alias $explicitGroupAlias with owner dataverse alias $ownerDataverseAlias";
    $this->log($logPrefix . $msg);

    return true;
  }
}
