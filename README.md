# headlessScreenshotsPHP
headlessScreenshotsPHP is a php command line script for taking screenshots with headless Google chrome. 

This script was created along side [https://github.com/VerifiedJoseph/GDPR451](https://github.com/VerifiedJoseph/GDPR451) and has very limited functionality. If you are looking for script or library to take full page screenshots please use Google's [Puppeteer library](https://developers.google.com/web/tools/puppeteer/) for Node.js.

## Command line options
```
--urls="FILE PATH" 	Use a custom urls csv file
--disable_table 	Disable results table creation
```

## Dependencies (via Composer)
```
league/csv
league/climate
mikehaertl/php-shellcommand
```
## Limitations
- Script can't create full page screenshots.