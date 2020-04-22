# Jira to Github Issues Migration

We are using the [Github Import API
Beta](https://gist.github.com/jonmagic/5282384165e0f86ef105) to migrate issues
from Jira via REST API to Github.

To keep all history we are importing issues from Jira.

Configuration: Create a `.env` file with the following environment variables:

```
JIRA_URL=https://jira.atlassian.net/
JIRA_USER=email@example.com
JIRA_TOKEN=123456789
GITHUB_USERNAME=sheldoncooper
GITHUB_TOKEN=1234abcd5678edgh
GITHUB_ORG=caltach

ISSUE_TYPES=Epic,Bug,Task,Spike,Enhancement

ASSIGNEES='{ "[accountId]": "sheldoncooper" }'

PROJECTS='{ "AP": "awesome-project" }'

CLOSED_STATES=Resolved,Closed,Done,Fixed,Løst

```

This repository contains all the scripts necessary for the migration. Run in the following order, as `php version_to_milestones [PROJECT KEY]`:

* `version_to_milestones.php` script to download all versions of a project and create equivalent
  milestones in Github Issue Tracker. It keeps a map of version-id/number to
  milestone-id in the data directory for the import.

* `export_jira_tickets.php` script to download all Jira issues per project and put them into a
  folder, one file per issue with the Github import API format. This
  script is using the Github milestones and labels as input.

* `import_tickets.php` script to push all tickets to a Github import API queue and poll for
  status, assigning each downloaded jira issue its appropriate Github Issue ID.

* `issue_numbers.php` script to fetch all imported tickets from Github and store the mapping
  into a file in the data folder that is needed for further processing.

* `attachments.php` script uses the imported issue numbers to create secret
  Gists for non-binary attachments of a Jira ticket and links them into the
  Github ticket by posting a comment.

* `assign_label.php` script to create labels according to Resolutions and Issue Types
  for a Jira Project in Github Issue tracker from the previously fetched import issue numbers.

* `generate-issues-map.php` script to pull all tickets from Github, extract the Jira Ticket Number
  from it and create a map of Jira Ticket Key to Github Id for future Redirects
  when Jira is decommissioned.
