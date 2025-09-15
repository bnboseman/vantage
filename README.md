# Cochrane Crawler

A simple PHP CLI application that crawls the Cochrane Library
by topic, collects metadata for each review, and saves the results to a file.
 
## Features

- Browse the Cochrane Library topics from a numbered menu.
- Select a topic and automatically crawl through all of its result pages.
- Extract and save for each review:
  - URL
  -Topic 
  - Title 
  - Authors 
  - Publication Date
- Progress indicator while fetching results (Processing page X of Y…).
- Export results to a file (pipe-delimited, CSV, JSON, or plain text).

```
==============================
 Welcome to the Cochrane Crawler
==============================

Select a topic to fetch reviews:

0. Allergy & intolerance
1. Blood disorders
2. Cancer
...
36. Wounds

Enter the number of your choice: 0

Fetching results for 'Allergy & intolerance'...
  -> Processing page 1 of 3
  -> Processing page 2 of 3
  -> Processing page 3 of 3
63 reviews have been found.
Enter a filename to save the results (e.g. cochrane_reviews.txt):  
```