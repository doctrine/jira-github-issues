# Jira to Github Issues Migration

We are using the [Github Import API
Beta](https://gist.github.com/jonmagic/5282384165e0f86ef105) to migrate issues
from Jira via REST API to Github.

Reasons for our move are the constant Spam Problems on our public Jira that we couldn't
get under control and the amount of maintenance we have to put into Jira.

Downsides of the migration are:

- the lack of multi milestone support on Github
- the lack of "good" search engine and filtering on Github issues
- the general wonkyness of Github issues
- no security issue support

To keep all history we are importing issues from Jira.

Configuration: Create a `.env` file with the following environment variables:

```
JIRA_URL=http://server/path/to/jira
JIRA_USER=foo
JIRA_PASSWORD=bar
GITHUB_USERNAME=name of the user importing the tickets
GITHUB_TOKEN=token for the user, propably requires admin token with everything
```

This repository contains all the scripts necessary for the migration.

* `version_to_milestones.php` script to download all versions of a project and create equivalent
  milestones in Github Issue Tracker. It keeps a map of version-id/number to
  milestone-id in the data directory for the import.

* `export_jira_tickets.php` script to download all Jira issues per project and put them into a
  folder, one file per issue with the Github import API format. This
  script is using the Github milestones and labels as input.
  **REQUIRES CHANGES** to the `$knownIssueTypes` and the `$knownAssigneesMap`
  variables for mapping issue types to labels in a following step and assigning
  the right Github users as creator or assignee of an issue.

* `import_tickets.php` script to push all tickets to a Github import API queue and poll for
  status, assigning each downloaded jira issue its appropriate Github Issue ID.

* `issue_numbers.php` script to fetch all imported tickets from Github and store the mapping
  into a file in the data folder that is needed for further processing.

* `attachments.php` script uses the imported issue numbers to create secret
  Gists for non-binary attachments of a Jira ticket and links them into the
  Github ticket by posting a comment.

* `assign_label.php` script to create labels according to Resolutions and Issue Types
  for a Jira Project in Github Issue tracker from the previously fetched import issue numbers.

* `jira_versions_to_github_releases.php` script to pull all versions from Jira and prepare a list of releases to
  create on Github, including release notes with adjusted ticket references and
  then push them. This script creates a hashmap of Jira Version Id to Github
  Release Id for future Redirects when Jira is decommissioned. **INCOMPLETE**

* `generate-issues-map.php` script to pull all tickets from Github, extract the Jira Ticket Number
  from it and create a map of Jira Ticket Key to Github Id for future Redirects
  when Jira is decommissioned.

Want to use this as external party? Things to watch out for:

- There is some configuration code in `export_jira_tickets.php` that must be
  adjusted.
- Also in `export_jira_tickets.php` is commeted out code for handling inward
  and outward links between tickets.  For us, the commented version currently
  generates Markdown links to the Jira URLs, where we have redirects in place
  to go to the Github issue.
- The Markdown converter currently detects Doctrine Jira Project Keys and converts
  them again to links to the old jira that then get redirected to the Github tracker
  using a script on top of `generate-issues-map.php`, which also needs project key adjustments.
