# com.joineryhq.stepw

## CiviCRM: Stepwise Workflows

Provides for configuration of workflows consisting of CMS pages and CiviCRM FormBuilder forms.

## What it does:

See the concept demo in this lightning talk from CiviCon 2025:
https://youtu.be/nEm2jZOv6bY?si=Wy3IM9l301vExUsM

### Allows for configuring multiple workflows, where each workflow:
- Has a distinct starting URL
- Provides the user with a configured set of steps, in which each step:
  - Can be a WordPress page OR a FormBuilder form
  - Can be expressed in more than one user-selected optional format (e.g. if Step
    2 offers two options "Article A" and "Article B", then Step 3 will display
    either A or B based on the user's selection.)
  - Perform validation of user submissions and end the users's workflow session
    if submitted values indicate the user is not qualified to continue.

### Provides a report template
CiviReport users may wish to use the included report template "Stepwise Workflow
Submissions" which aims to make it easy to create a saved per-workflow report
showing one row per submission, with columns for various submitted data fields
across all forms in the workflow.


### Use case assumptions:
- Users are not logged in.
- It's desirable that all FormBuilder submissions are linked to a single contact
  representing the user who began the workflow session.

## Why it's needed:
Without this extension, it's very difficulat to have an anonymous user submit
multiple FormBuilder forms and then to have those multiple submissions linked to
a single CiviCRM contact. Less-than-ideal workarounds would include:
- Adding identical identifiying fields (e.g. First Name, Last Name, Primary Email)
  to all forms, and then relying upon CiviCRM's dedupe rules to automatically
  identify a single contact. Drawbacks:
  - Tedious for user to repeatedly enter identical data.
  - User inconsistency/typos (Bob vs Robert, etc) can still result in multiple 
    contacts.
- Asking staff to manually review submissions and merge the duplicate contacts. 
  Drawbacks:
  - This is tedious, error-prone, and delays processing of sumissions for
    real-world use.

## Requirements:

- CiviCRM (of course)
- WordPress
- WordPress plugin "CiviCRM Stepwise" (https://github.com/JoineryHQ/wp-stepwise)
  This extension relies upon that WordPress plugin for insertion of "next" buttons
  at each step in the workflow.

## FormBuilder forms for use in workflows:
This extension expects that any FormBuilder form used in a workflow will match
the following requirements:
- Contain one Individual entity named 'Individual1'
- Contain one Activity entity named 'Activity1'
- Submissions: Open
- Accessible on front-end of website
- Permission: "Generic: Allow all users (including anonymous)"

FormBuilder forms used in any workflow may still be:
- Used independently if you like; in such usage they will not be affected by this
  extension and will only do what they would normally do without this extension.
- Used in multiple workflows; in this usage, any workflow-based handling of the
  form will be on a per-workflow basis. Thus you could, for example, have two
  workflows which both use a common "Step 1" form to collect basic demographic
  information, but which then each take the user down a very different set of
  subsequent steps.

It's recommended that you test your FormBuilder forms individually, both in display
and in storage of submitted data, so that you can be sure they're working properly
before testing the entire workflow.

## WordPress pages for use in workflows:
In order to allow the "next" button(s) to appear anywhere on the page, this extension
expects that any page used in a workflow will contain the `[stepwise-button]`
shortcode in the place where such button(s) should appear.

WordPress pages used in any workflow may still be:
- Used independently if you like; outside of an active workflow session, the
  `[stepwise-button]` shortcode will be replaced with an empty string, so that
  the page appears as a normal WordPress page.
- Used in multiple workflows; in this usage, any workflow-based handling of the
  page will be on a per-workflow basis. Thus you could, for example, have two
  workflows which both use a common "Step 1" WordPress page to welcome users and
  introduce the workflow, but which then each take the user down a very different
  set of subsequent steps.

## Configuration:
### File-based configs

This extension does not yet have a UI for configuring the actual workflows.
Instead the configuration is file-based. To configure workflows:
- Locate the file at `./CRM/Stepw/Fixme/_stepwLocalData_example.php` (yes, this
  file path contains a directory named 'Fixme'; you may infer that this is related
  to the 'alpha' status of this extension, and that the intention is to develop
  a browser-based UI to handle this configuration task.
- Copy this file to `_stepwLocalData.php` adjacent to your `civicrm.settings.php`
  file (likely at `[doc-root]/wp-content/uploads/civicrm/_stepwLocalData.php`)
- Edit according to the inline comments in that file.

### On-screen configs

Within CiviCRM, this extension offers two config screens, under Administer ->
Customize Data and Screens -> Stepwise Workflows:
- Workflows: This page lists all workflows defined in the config file, and provides
  an initial "starting" URL for each, which may be shared publicly. There is
  actually no configuration editing to be done on this page, as it just diplays
  what is configured elsewhere.
  - IMPORTANT: The starting URL for each workflow must be shared to your users
    as shown on this page (or as copied with the displayed "Copy URL" link for
    the given workflow). As soon as you put this URL into a browser, you'll be
    redirected to a unique single-use URL that begins a workflow session for you,
    so you cannot then copy and share _that_ URL with others.
- Settings: Offers minor in-app settings; at time of writing this is limited to
  a single option to enable increased logging for research and debugging purposes.

### Report configuration
After you've added a workflow configuration in the file-baed config, when viewing
the Workflow list at Administer -> Customize Data and Screens -> Stepwise Workflows
-> Wofklows, you'll notice the last display column in that list is labeled "Report".
If you wish, you can create a CiviReport instance (probably based on the "Stepwise
Workflow Submissions" report template mentioned above), to display submissions
from this workflow.

Once you have that report instance ID, you can issue an Api4 update to link that
report to the workflow, so that it displays in this "Report" column for easy
access. For example, to link report instance id=44 to workflow id=1, you could
issue this `cv` command at the command line (or do the equivalent in CiviCRM's
in-app Api4 Explorer):  
`cv api4 StepwWorkflow.update +v report_instance_id=44 +w 'id = 1`


## Proposed improvements:

The developer is open to pull requests and sponsorship for making the following
improvements:
- Support for Drupal or other CMS frameworks (would likely require a CMS-specific
  module/plugin analogous to WordPress plugin "CiviCRM Stepwise" mentioned above).
- Creation of a UI for configuring workflows (and thus deprecation of file-based
  configuration).


## Support

Support for this extension is handled under Joinery's ["Active Support" policy](https://joineryhq.com/software-support-levels#active-support).

Public issue queue for this extension: [https://github.com/JoineryHQ/com.joineryhq.stepw/issues](https://github.com/JoineryHQ/com.joineryhq.stepw/issues)
