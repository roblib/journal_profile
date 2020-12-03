#!/usr/bin/env bash

# Create sample submitter and reviewer roles
# and generate Journal, Issue and Article
# sample content via devel_generate.
#
# Usage: generate_sample_content.sh <site-url> <account password>

SITEURL=${1:-http://localhost}
ACCOUNTPASS=${2:-admin}


drush -l $SITEURL ucrt "Sample Submitter" --password=ACCOUNTPASS

drush -l $SITEURL user:role:add "journal_article_creator" "Sample Submitter"

drush -l $SITEURL ucrt "Sample Reviewer" --password=ACCOUNTPASS

drush -l $SITEURL user:role:add "journal_article_reviewer" "Sample Reviewer"

drush -l $SITEURL pm-enable devel_generate

drush -l $SITEURL devel-generate-content 1 --bundles=journal
drush -l $SITEURL devel-generate-content 8 --bundles=issue

drush -l $SITEURL devel-generate-terms --bundles=article_section 3
drush -l $SITEURL devel-generate-terms --bundles=article_keywords 3
drush -l $SITEURL  devel-generate-terms --bundles=disciplines 3
drush -l $SITEURL devel-generate-terms --bundles=subjects 3
drush -l $SITEURL devel-generate-content 40 --bundles=journal_article