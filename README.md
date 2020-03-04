[![No Maintenance Intended](http://unmaintained.tech/badge.svg)](http://unmaintained.tech/)

Mageploy is an extension for Magento deployment automation based on Admin actions tracking and replication between different project environments.

How does it work?
-----------------
Basically it's a tracker of invocations of Magento Controller Actions. It's based on the recording of actions in two files:

* `{{configured_folder}}/mageploy_all.csv`
* `{{configured_folder}}/mageploy_executed.csv`

The **mageploy_all.csv** file is **global** and should be put **under version control**. This file keeps track of each action invoked, storing parameters converted and serialized.

The **mageploy_executed.csv** is **local** and should **not be put under version control**. This file keeps track of actions invoked locally and is used to avoid invocations of already invoked actions.

The `{{configured_folder}}` placeholder can be either a relative or an absolute path; in case you use a relative path, 
the Magento root folder will be used as base path.

**Warning:** don't change the `{{configured_folder}}` after you have already started recording actions if you don't want to
 loose previous tracked actions.

Once you install mageploy and activate tracking (active by default), Mageploy's Trackers will store action invocations.

Once you commit and push your changes you will also push the **mageploy_all.csv**. Developers which will pull your changes will get the global list of actions updated and can replicate missing invocations by using the command line tool **shell/mageploy.php**.

To learn more about Mageploy, please refer to the Documentation on the  [Official Website](http://www.mageploy.com/).

AS-IS
-----
At the moment the following trackers have been developed for (not tested so much):

* Attributes
* Attribute Sets
* System Config (uncomplete)
* Categories
* CMS Blocks
* CMS Pages
* Websites
* Store Groups
* Store Views

TO-BE
-----
The System\Config tracker is only a POC. There are a lot of sections and groups in the System\Config and we should provide encorders/decoders for all of them.

For example in some circumstances you can perform file ulpoad; this is not handled. At the same time IDs are not translated into UUIDs but there can be IDs which are specific to current installation. To handle all these cases the System\Config tracker should be splitted into sections/groups trackers, each of them applying its enconding/deconding policy.
In Categories tracker file uploads are not handled yet.

So many more trackers should be developed for:

* Complete System Config Sections/Groups
* Complete Categories
* Taxes
* URL Rewrites
* Promotions
* Transactional Emails
* Order Statuses

RELEASE NOTES
-------------
Here we keep track of major changes between different versions.

A change in the third version number part indicates minor changes or fixes.

A change in the second version number part indicates changes in CSV format which implies that previous encoded CSVs could not be decoded any more.

* 1.2.2 - fix previous version; add check for writable files and related warning in header banner
* 1.2.1 - DON'T USE THIS VERSION - action recording is broken
* 1.2.0 - add possibility to configure the folder where CSV files are created.
* 1.1.3 - replaced deprecated join and split functions with equivalent implode and explode.
* 1.1.2 - fix on displaying of error messages while executing actions.
* 1.1.1 - fix on decode() declaration in Abstract class.
* 1.1.0 - changed encoding/decoding for Block tracking; added code to reset Magento at every Action execution to avoid issues with objects in memory, like the Register. Added Tracket version control to prevent decoding of rows encoded with a different version of a Tracker.
* 1.0.1 - fixed bug in CMS Blocks tracking: saving existing blocks didnt' work because encoding/decoding ignored block_id parameter.
* 1.0.0 - first release
