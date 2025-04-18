# COmanage Registry Dataverse Provisioner for CADRE

## Design

### Preliminaries

The Registry version 4.x documentation on writing plugins is at
[https://spaces.at.internet2.edu/display/COmanage/Writing+Registry+Plugins](https://spaces.at.internet2.edu/display/COmanage/Writing+Registry+Plugins).

Documentation specifically on writing provisioner plugins is at
[https://spaces.at.internet2.edu/display/COmanage/Provisioner+Plugins](https://spaces.at.internet2.edu/display/COmanage/Writing+Registry+Plugins).

The Dataverse API Guide for version 6.2 is at
[https://guides.dataverse.org/en/6.2/api/](https://guides.dataverse.org/en/6.2/api/).

The current version of the plugin targets Dataverse version 6.2.

### Dataverse Server Configuration

Manipulating Dataverse people and explicit group objects requires use of the Dataverse API
and so the Dataverse server should be configured with `:BlockedApiPolicy` set to `unblock-key`
and `:BlockedApiKey` set. See the Dataverse documentation at
[https://guides.dataverse.org/en/6.2/installation/config.html#blockedapipolicy](https://guides.dataverse.org/en/6.2/installation/config.html#blockedapipolicy).

### Mapping Between Dataverse and Registry Objects

#### People

| Dataverse Authenticed User Attribute | CO Person Object | Example | Notes |
| ------------------------------------ | ---------------- | ------- | ----- |
| identifier | Identifier of configured type | skoranda | Identifier type chosen during plugin configuration. Usually an Extended Type. |
| persistentUserId | Identifier of configured type | http://cilogon.org/serverT/users/27326098 | Identifier type chosen during plugin configuration. OIDC sub logical choice.
| id | Identifier of type Provisioning Target | 1:11 | Returned by Dataverse when creating user and saved by plugin after prepending the ID of the provisioner instance. |
| authenticationProviderId | None | https://archive-dev.ada.edu.au/ | Part of plugin configuration. Static for all users. Provided by Dataverse administrator. |
| firstName | Name (given) | Scott | Name type chosen during plugin configuration (e.g. Official) |
| lastName | Name (family) | Koranda | Name type chosen during plugin configuration (e.g. Official) |
| displayName | None | | Does not seem to be required by Dataverse API and then is automatically generated? |
| email | EmailAddress of configured Type | skoranda@illinois.edu | EmailAddress type chosen during plugin configuration (e.g. Official) |

#### Groups

| Dataverse Explicit Group | CO Group Object | Example | Notes |
| ------------------------ | --------------- | ------- | ----- |
| displayName | Name | ADA-ANU Poll Dataverse-doi:10.26193/CI4Z2S | The DOI can be exchanged using DOI API for specific Dataverse server citation URL, and then the Dataverse queried to find the owning Dataverse Collection in which to create the group.|
| | Identifier of configured type | 10.26193/CI4Z2S | Duplicates DOI that is part of Name |
| description | Description | Authorized users for ADA-ANU Poll Dataverse-doi:10.26193/CI4Z2S |  |
| owning collection identifier | See notes on displayName | anupoll | |
| aliasInOwner |  | authorized_doi:10.26193/CI4Z2S | |

### How Registry Objects Work Together  

For each Dataserver to be provisioned the following Registry objects form a "set" and should use
corresponding names and descriptions when they are created and configured:

- An Extended Identifier type that will hold the Dataverse identifier value for a CO Person record. An example
  is `adaproddataverse`.
- An Automatic Identifier Assignment that automatically creates the Identifier of the Extended Identifier type using a rule
  and attaches it to the CO Person record. An example Automatic Identifier Assignment, rule, and value are
     - `ADA PROD Dataverse`
     - `(g:1)(f)[2:(#)]` (first character lowercase from given name prepended to lowercase family name and prepended if
       necessary with a numerical disambiguator that starts with `2`)
     - `skoranda7`
- A Server of Type `HTTP` that holds the URL and API  token necessary to invoke the Dataverse server API. An example
  is `ADA PROD Dataverse` with URL `https://dataverse.ada.edu.au`.
- A ProvisioningTarget using plugin `DataverseProvisioner`. An example description is `ADA PROD Dataverse`.

Each instance of the ProvisioningTarget must be configured with a second Server of Type `HTTP` that represents
a DOI API server. Multiple ProvisioningTarget instances may be configured to use the same DOI Server.

### Registry CO Group Naming Convention and Dataverse Explicit Group Considerations

In Dataverse a role is a set of permissions and a role may be assigned to an explict group. Each member of the
explicit group then is given the set of permissions.

Each explicit group is ''owned'' by a dataverse within a Dataverse server and different dataverses
may ''own'' explicit groups that have the same name. For example, the dataverses `ANU Poll 2008: Environment`
and `ANU Poll 2009: Economics` may both own and use distinct explicit groups named `ANU Staff` for
managing authorization to the dataverse and the datasets and collections within it. Further, different
Dataverse servers in the community (represented in Registry by unique instantiations of the plugin) and
their dataverses may also use distinct explicit groups with the same names.

Registry, however, at this time only supports a flat CO Group namespace and no two CO Groups
may share the same name.

So a naming convention and logic are required to disambiguate and map a CO Group to a specific
explicit group owned by a specific dataverse in a specific Dataverse server.

The naming convention for CO Groups is

```
<dataverse name>-doi:<DOI>
```

Also note that the convention is that the CO Group has an attached Identifier
of the configured DOI type that holds the DOI (value only, no prefix string 'doi').

The logic for mapping the CO Group name to an owner dataverse within a Dataverse server is:

1. Find the Identifier attached the CO Group with the configured DOI type and extract the DOI.
1. Query the configured DOI server and from the returned metadata extract the Dataverse server.
1. Query the Dataverse server with the DOI to obtain the dataverse owner of the explicit group.

Note that for development and testing purposes, when the plugin is so configured the second
step above is skipped and the plugin assumes that all CO Groups are mapped to the Dataverse
server associated with the plugin.

### Implementation Details

- Each instantiation of the plugin targets one and only one Dataverse server instance.

- The plugin assumes that the Dataverse server is configured with an `unblock-key` (see above discussion)
and so configuration (see below) requires both an API token and an Admin API token (used as the
`unblock-key`).

- The plugin only takes action on the following CO Person record events in Registry:
   - CoPersonAdded
   - CoPersonPetitionProvisioned
   - CoPersonPipelineProvisioned
   - CoPersonReprovisionRequested
   - CoPersonUpddated

   All of those events are treated identitically by the plugin.

   Note especially that the Dataverse API does *not* support deleting a person
   object and the plugin does *not* take any action when a CO Person record is
   expunged or the Status is set to anything other than Active.

- The plugin only takes action on the following CO Group events in Registry:
   - CoGroupReprovisionRequested
   - GoGroupUpdated

   All other provisioner events are ignored by the plugin.

- The plugin will *only* attempt to create a user in Dataverse when all of the
following are true:
   - The CO Person record has a Status that is Active.
   - The CO Person record has an attached Identifier of the
     configured Dataverse Identifier type.
   - The CO Person record has an attached Identifier of the
     configured Dataverse Persistent User Identifier type.
   - The CO Person record has a Name of the configured 
     Dataverse Name type.
   - The CO Person record has an attached EmailAddress of the
     configured Dataverse Email type.
   - The CO Person is a member of at least one authorization group,
     that is a member of a CO Group with the configured Group Identifier type.
   - The CO Person record has not been successfully provisioned previously.

- Before attempting to create a user in Dataverse the plugin queries the `admin`
  API at `/api/admin/list-users` and query string `searchTerm` with the value
  of the EmailAddress attached to the CO Person record. Only when that search
  returns empty does the plugin attempt to create a user in Dataverse.

- The plugin will fail to create a user in Dataverse when any of the
following are true in the Dataverse database:
   - A user already exists with the email address.
   - A user already exists with the identifier.
   - A user already exists with the persistentUserId.

- The plugin will, however, reconcile an existing user in Dataverse with
the same email address and will attempt to edit in Registry the Identifier of the
configured Dataverse Identifier type so that it is synchronized with the existing
value in Dataverse. Since no two CO Person records may have the same Identifier
of the same type with the same value, however, it is possible for the reconcilliation
and synchronization for an existing Dataverse user with the same email address
to fail. When that happens manual intervention by the Dataverse admin and the
Registry CO admin will be required to effectively synchronize the CO Person
record with the Dataverse user.

## Configuration

All plugin configuration must be done as a Registry CO administrator.


### Create Extended Identifier Types

Repeat the steps below once for each Dataverse server.

1. Browse to `Configuration > Extended Types`.
1. In the drop-down for `For Attribute` choose `Identifier (CO Person, Group)`, which is the default.
1. Click `FILTER`.
1. Click `Add Extended Type`.
1. Verify that the `Attribute` field in the form has value `Identifier (CO Person, Group)`.
1. In the Name field enter a unique value, for example `dataverseid`. For a second Dataverse server
   the value might be `dataverseid2`, and for the third `dataverseid3`, and so on.
1. In the `Display Name` field enter a descriptive name. This is usually the name of a Dataverse server
   such as `ADA PROD ANU Dataverse` or `ADA DEV Dataverse`.
1. For `Status` choose `Active` from the drop-down menu.
1. Click `ADD`.

Complete the steps below once to create a DOI Identifier type to use with Registry CO Groups.

1. Browse to `Configuration > Extended Types`.
1. In the drop-down for `For Attribute` choose `Identifier (CO Person, Group)`, which is the default.
1. Click `FILTER`.
1. Click `Add Extended Type`.
1. Verify that the `Attribute` field in the form has value `Identifier (CO Person, Group)`.
1. In the Name field enter `doi`. 
1. In the `Display Name` field enter `DOI`.
1. For `Status` choose `Active` from the drop-down menu.
1. Click `ADD`.

### Create Automatic Identifier Assignments

Repeat the steps below once for each Dataverse server.

1. Browse to `Configuration > Identifier Assignments`.
1. Click `Add Identifier Assignment`.
1. For `Description` enter the name of a Dataverse server, for example `ADA PROD ANU Dataverse`.
1. For `Status` choose `Active` from the drop-down menu.
1. For `Context` choose `CO Person` from the drop-down menu.
1. For `Type` choose the type from the drop-down menu that corresponds to the value entered for `Description`,
   for example `ADA PROD ANU Dataverse`.
1. Do not tick the box for `Login`.
1. Do not enter a value for `Order`. The value will be auto-assigned.
1. For `Algorithm` select `Sequential` from the drop-down menu.
1. Ignore the `Plugin` field.
1. For `Format` enter `(g:1)(f)[2:(#)]`. This will cause the first character from the users Give Name
   in lowercase to be prepended to the lowercase Family Name to create the Identifier value. If a value
   already exists then a digit will be used as a disambiguator, starting with `2` and increasing
   as necessary until the next unique value is found and assigned.
1. Leave `Minimum Length` empty.
1. Do not tick `Enable Transliteration`.
1. For `Permitted Characters` choose `AlphaNumberic Only` from the drop-down menu.
1. Leave `Minimum Collision Number` empty.
1. Click `SAVE`.

### Create the Registry Server Object for A Dataverse Server

Repeat the steps below once for each Dataverse server.

1. Browse to `Servers > Add a New Server`.
1. Provide a Description for the Server. This is usually a meaningful name, for example
   `Production Dataverse (Australian Data Archive)`.
1. Choose `Active` for the Server Status.
1. Choose `HTTP` for the Server Type.
1. Click `ADD`.
1. For `Server URL` enter the URL for the Dataverse server, for example
   `https://dataverse.ada.edu.au`.
1. Leave the field `Username` empty.
1. For `Password or Token` enter a valid API token for the Dataverse server. Do not
   use an `admin` API token.
1. For `HTTP Authentication Type` choose `None`.
1. Tick the box for `Require certificate verification`.
1. Tick the box for `Require name verification`.
1. Click `SAVE`.

### Create the Registry Server Object for a DOI Server

1. Browse to `Servers > Add a New Server`.
1. Provide a Description for the Server. This is usually a meaningful name, for example
   `doi.org API Server`.
1. Choose `Active` for the Server Status.
1. Choose `HTTP` for the Server Type.
1. Click `ADD`.
1. For `Server URL` enter the URL for the DOI API server, for example
  `https://doi.org`.
1. Leave the field `Username` empty.
1. Leave the field `Password or Token` empty.
1. For `HTTP Authentication Type` choose `None`.
1. Tick the box for `Require certificate verification`.
1. Tick the box for `Require name verification`.
1. Click `SAVE`.

Multiple DataverseProvisioner instantiations may use the same DOI API server.

### Create the Provisioning Target

Repeat the steps below once for each Dataverse server.

1. Browse to `Configuration > Provisioning Targets > Add Provisioning Target`.
1. Provide a Description for the provisioning target. This is usually a meaningful name, for example
   `Production Dataverse`.
1. Choose `DataverseProvisioner` from the `Plugin` drop-down menu.
1. Choose `Automatic Mode` from the `Status` drop-down menu.
1. Do not choose a `Provisioning Group`.
1. Do not choose `Skip If Associated With Org Identity Source`.
1. Leave the field `Order` empty. It will later be auto-assigned.
1. Click `ADD`.
1. For `Dataverse Server` choose a Server object from the drop-down menu that represents a Dataverse server.
1. For `Admin API Token` enter a valid admin API token suitable for unblocking the `/api/admin` endpoint.
1. For `DOI Server API` choose a Server object from the drop-down menu that represents a DOI API server to use
   when mapping DOI values to Dataverse server.
1. For `Dataverse Identifier Type` select from the drop-down menu the Identifier of the type configured
   in the step above "Create Extended Identifier Types".
1. For `Dataverse Persistent User Identifier Type` select `OIDC sub` from the drop-down menu.
1. For `Dataverse Name Type` select `Official` from the drop-down menu.
1. For `Dataverse Email Type` select `Official` from the drop-down menu.
1. For `Group Identifier Type` select `DOI` from the drop-down menu.
1. For `Dataverse Authentication Provider ID` enter the value configured by the Dataverse administrator in
   the Dataverse server.
1. If operating in testing mode and a DOI server will not be able to map from the DOI to a Dataverse server
   instance then tick the box for `Skip Dataverse Server Mapping`. This will cause each CO Group representing
   an explicit group owned by a dataverse to be created in each Dataverse server represented by a
   provisioning target (instance of the plugin).
1. Click `SAVE`.

## Testing

