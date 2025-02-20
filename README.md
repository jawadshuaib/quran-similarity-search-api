# Backend for Quran Similarity Search

This API provides access to querying a MySQL database for finding similar verses in the Quran. Front-end for this code is available at https://github.com/jawadshuaib/quran-similarity-search

## Overview

This project is a PHP-based backend for the Quran Similarity Search application. It allows users to find linguistically similar verses in the Quran by querying a MySQL database. The API provides several endpoints to fetch information about surahs, verses, and their similarities.

## Live Examples of the API

- Fetch Surah Info: [http://localhost/quran/api/surah_info/?surah_number=2](http://localhost/quran/api/surah_info/?surah_number=2)
- Fetch Verse Info: [http://localhost/quran/api/verse/?surah_number=10&aya_number=49](http://localhost/quran/api/verse/?surah_number=10&aya_number=49)
- Fetch Similar Verses: [http://localhost/quran/api/similar/?translation=456&surah_number=1&aya_number=2](http://localhost/quran/api/similar/?translation=456&surah_number=1&aya_number=2)

## Project Structure

- **api/**: Contains the API endpoints for fetching data.
- **connection/**: Contains the database connection configuration.
- **src/**: Contains the source code for the application.
- **static/**: Contains static files such as CSS and images.
- **common.php**: Contains common functions and constants used throughout the project.
- **connection.php**: Handles the database connection setup.

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.6 or higher
- Composer (for managing PHP dependencies)

### Installation

1. **Clone the repository:**

   ```
   git clone https://github.com/jawadshuaib/quran-similarity-search-api.git
   cd quran-similarity-search-api
   ```

2. **Install dependencies:**

   ```
   composer install
   ```

3. **Configure the database:**

   - Create a MySQL database and import the provided SQL schema.
   - Update the database configuration in `connection/connection.php` and `connection/pwd/pwd.php` with your database credentials.

4. **Run the PHP server:**

   ```
   php -S localhost:8000
   ```

5. **Access the API:**

   Open your browser and navigate to `http://localhost:8000` to access the API endpoints.

## API Endpoints

- **Fetch Keywords Search**: `src/api/fetch-keywords-search.js`
- **Fetch Lemma Relatives**: `src/api/fetch-lemma-relatives.js`
- **Fetch Similar Verses**: `src/api/fetch-similar-verses.js`
- **Fetch Surah Info**: `src/api/fetch-surah-info.js`
- **Fetch Verse Info**: `src/api/fetch-verse-info.js`
- **Fetch Word Info**: `src/api/fetch-word-info.js`
- **Google Translate**: `src/api/google-translate.js`

## Configuration

### Database Connection

The database connection is configured in `connection/connection.php`. The file includes logic to switch between local and remote database configurations based on the hostname.

### Common Functions

The `common.php` file contains various utility functions used throughout the project, such as:

- **does_lemma_exist($lemma)**: Checks if a lemma exists in the database.
- **get_quranic_words_for_lemma($lemma)**: Retrieves Quranic words for a given lemma.
- **has_lemma_for_quranic_word($quranicWord)**: Checks if a Quranic word has a corresponding lemma.
- **get_lemma_for_quranic_word($quranicWord)**: Retrieves the lemma for a given Quranic word.
- **is_translation_id_arabic_simple($translationId)**: Checks if a translation ID corresponds to the Arabic simple translation.
- **total_verses_above_cut_off($translationId, $surahNumber, $ayaNumber, $method, $cutOff)**: Counts the total verses above a specified cut-off value.
- **get_quranic_text($surahNumber, $ayaNumber)**: Retrieves the Quranic text for a given surah and aya.
- **get_translation($translationId, $surahNumber, $ayaNumber)**: Retrieves the translation for a given surah and aya.
- **does_aya_exist($surahNumber, $ayaNumber)**: Checks if an aya exists in the database.
- **does_surah_exist($surahNumber)**: Checks if a surah exists in the database.
- **does_translation_for_this_method_exist($translationId, $method)**: Checks if a translation exists for a specified method.
- **get_cut_off($method)**: Retrieves the cut-off value for a specified method.
- **pick_method($method)**: Picks the appropriate method for similarity calculation.
- **does_this_translation_exist($translationId)**: Checks if a translation exists in the database.
- **pick_default_translation_id()**: Picks the default translation ID.
- **return_single_array_objects($arr)**: Returns the arrays as single elements.
- **r($arr)**: Alias for `return_single_array_objects`.
- **get_properties($query)**: Retrieves the field names and values of any SQL table.
- **make_safe($str)**: Sanitizes a string to prevent SQL injection.
- **xss_clean($data)**: Cleans data to prevent XSS attacks.
- **count_rows($query)**: Counts the number of rows in a query result.
- **connect_to_database()**: Connects to the database.

## Contact

For any inquiries, please reach out to [Jawad Shuaib](mailto:jawad.php@gmail.com).
