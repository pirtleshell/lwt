# LWT REST API Documentation

This documentation provides an overview of the REST API endpoints available for LWT-community. 
The LWT API allows developers to interact with various features of the application, 
such as retrieving media paths, getting sentences containing a specific term, 
managing settings, and more. 

## Base URL

The base URL for all API endpoints is: `http://[base_url]/api/v1`, 
where `base_url` is your instance URL (e. g. `localhost:8080`). 

## Authentication

Currently, the LWT API does not require authentication. However, authentication 
might be implemented in the future. Ensure that appropriate security measures are 
implemented on the client-side to protect user data.

## Versioning

The REST API should follow [semantic versioning](https://semver.org/). 
In the API URL, `v1` indicates "MAJOR version 1", and so on for the future versions.
A track of all changes is kept in the [CHANGELOG.md](./CHANGELOG.md).

## GET API Endpoints

### Get Media Paths

Retrieves the media paths to the audio and video files in the media folder.

- **Endpoint**: `/media-paths`
- **Method**: GET
- **Arguments**: None

### Get Next Word to Review

Retrieves the next word to be reviewed.

- **Endpoint**: `/review/next-word`
- **Method**: GET
- **Arguments**: `test_sql`, `word_mode`, `lg_id`, `word_regex`, `type`

### Get Tomorrow's Reviews Number

Retrieves the number of reviews scheduled for tomorrow.

- **Endpoint**: `/review/tomorrow-count`
- **Method**: GET
- **Arguments**: `test_sql`

### Get Sentences Containing New Term

Retrieves sentences that contain the specified term.

- **Endpoint**: `/sentences-with-term`
- **Method**: GET
- **Arguments**: `lg_id`, `word_lc`, and `advanced_search` (optional)

### Get Sentences Containing Registred Term

Retrieves sentences that contain the specified term.

- **Endpoint**: `/sentences-with-term/{term-id}`
- **Method**: GET
- **Arguments**: `lg_id` and `word_lc`


### Get Terms Similar to Another

Retrieves terms similar to the specified term.

- **Endpoint**: `/similar-terms`
- **Method**: GET
- **Arguments**: `lg_id` and `term`

### Get Theme Path

Retrieves the path for a file using the user theme.

- **Endpoint**: `/settings/theme-path`
- **Method**: GET
- **Arguments**: `path`

### Get Imported Terms

Retrieves the terms that were imported.

- **Endpoint**: `/terms/imported`
- **Method**: GET
- **Arguments**: `last_update`, `page` and `count`

### Get Term Translations

Retrieves the translations for a specific term.

- **Endpoint**: `/terms/{term-id}/translations`
- **Method**: GET
- **Arguments**: `text_id` and `term_lc`

### Get Phonetic Reading

Retrieves the phonetic reading for a specific text.

- **Endpoint**: `/texts/{text-id}/phonetic-reading`
- **Method**: GET
- **Arguments**: `text` and `lang`.

### Get Texts Statistics

Retrieves statistics for multiple texts.

- **Endpoint**: `/texts-statistics/{texts-ids}`
- **Method**: GET
- **Arguments**: `texts_id`

### Get API Version

Retrieves the version of the LWT API.

- **Endpoint**: `/version`
- **Method**: GET
- **Arguments**: None

## POST API Endpoints

### Save Setting

Saves user settings.

- **Endpoint**: `/settings`
- **Method**: POST
- **Arguments**: `key` and `value`

### Set Text Annotation

Sets the annotation for a specific text.

- **Endpoint**: `/texts/{text-id}/annotation`
- **Method**: POST
- **Arguments**: `elem` and `data`

### Update Audio Position

Updates the audio position for a specific text.

- **Endpoint**: `/texts/{text-id}/audio-position`
- **Method**: POST
- **Arguments**: `position`

### Update Reading Position

Updates the reading position for a specific text.

- **Endpoint**: `/texts/{text-id}/reading-position`
- **Method**: POST
- **Arguments**: `position`

### Decrement Term Status

Decrements the status of a term.

- **Endpoint**: `/terms/{term-id}/status/down`
- **Method**: POST

### Increment Term Status

Increments the status of a term.

- **Endpoint**: `/terms/{term-id}/status/up`
- **Method**: POST

### Set Term Status

Sets the status of a term to a new value.

- **Endpoint**: `/terms/{term-id}/status/{new-status}`
- **Method**: POST


### Add Translation

Adds a new translation for a term.

- **Endpoint**: `/translations/new`
- **Method**: POST

### Update Translation

Updates an existing translation for a term.

- **Endpoint**: `/translations/{term-id}`
- **Method**: POST

## Response Format

All API endpoints return responses in JSON format.
