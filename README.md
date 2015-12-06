# Jira to Github Issues Migration

We are using the [Github Import API
Beta](https://gist.github.com/jonmagic/5282384165e0f86ef105) to migrate issues
from Jira to Github.

Reasons for our move are the constant Spam Problems on our public Jira that we couldn't
get under control and the amount of maintenance we have to put into Jira.

Downsides of the migration are:

- the lack of multi milestone support on Github
- the lack of "good" search engine and filtering on Github issues
- the general wonkyness of Github issues
- no security issue support

To keep all history we are importing issues from Jira.

This repository contains all the scripts necessary for the migration.

* One script to create labels according to Resolutions and Issue Types
  for a Jira Project in Github Issue tracker.

* One script to download all versions of a project and create equivalent
  milestones in Github Issue Tracker, saving a map of version-id/number to
  milestone-id.

* One script to download all Jira issues per project and put them into a
  folder, one file per issue with the Github import API format. This
  script is using the Github milestones and labels as input.

* One script to push all tickets to a Github import API queue and poll for
  status, assigning each downloaded jira issue its appropriate Github Issue ID.

* One script to pull all versions from Jira and prepare a list of releases
   to create on Github, including release notes with adjusted ticket references.

* One script to push the releases to Github.
