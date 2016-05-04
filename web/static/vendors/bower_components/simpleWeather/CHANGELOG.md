# simpleWeather Changelog

For a more complete changelog you can always check out the [commit log](https://github.com/monkeecreate/jquery.simpleWeather/commits/master).

## v3.0.2 - June 2 2014

* Fixed result issue when more than one location was returned. [#90](https://github.com/monkeecreate/jquery.simpleWeather/issues/90)

## v3.0.1 - May 22 2014

* Fixed forecast thumbnail and image bug. [#88](https://github.com/monkeecreate/jquery.simpleWeather/issues/88)

## v3.0 - May 17 2014

* Complete rewrite! Removed over 100 lines of code without losing functionality.
* Now < 4.0 kB in size.
* Added forecast thumbnail image.
* Removed tomorrow in favor of forecast.
* Fixed http/https issue on API call [#79](https://github.com/monkeecreate/jquery.simpleWeather/pull/79) and images.
* Hat tip to [@defvayne23](https://github.com/defvayne23) for a quick code review.

## v2.7 - April 17 2014

* Added gulp and a build/release process.
* Fix for 3200 (not available) condition code and related image [#77](https://github.com/monkeecreate/jquery.simpleWeather/issues/77).
* Fixed my assumption of query being present [#72](https://github.com/monkeecreate/jquery.simpleWeather/issues/72). Hat tip to [@rjackson](https://github.com/rjackson).
* Some general code cleanup.

## v2.6 - February 26 2014

* Encoding URI for API call.
* Fixed formatting issues.
* Fixed alt temps bug.
* Hat tip to [@defvayne23](https://github.com/defvayne23) for the suggestions.

## v2.5 - February 5 2014

* Removed deprecated weather.search YQL table.
* Added geo.placefinder YQL table for searching locations. This allows you to get your location in a more forgiving format than before including latitude and longitude.
* Removed `options.zipcode` as `options.location` now accepts any combination of city, state, and zipcode.
* Removed `timeOfDay` as it was buggy and wouldn't return correctly anyways. Currently there is not a method to make this work accurately. See [#66](https://github.com/monkeecreate/jquery.simpleWeather/issues/66) for info.
* Removed `http:` from api call and image source to allow for https.

## v2.4 - January 22 2014

* Added four day forecast with recent Yahoo Weather API update.

## v2.3 - June 16 2013

* Added `timeOfDay` for custom day/night icons.
* Fix for new Yahoo API error result.

## v2.2 - April 20 2013

* Added high and low alt temps.
* Added alt temp unit.

## v2.1.2 - January 25 2013

* Just a version bump for jQuery plugin directory.

## v2.1 - November 18 2012

* Added WOEID location param for all.

## v2.0.1 - January 26 2012

* Plenty of code cleanup but no additions, fixes, or deletions.

## v2.0 - November 23 2011

* Added forecast condition code.

## v1.9 - October 3 2011

* Added alt temp.

## v1.8 - May 15 2011

* Fixed wind direction.
* Added calculations for the heat index.

## v1.7 - May 8 2011

* Added condition code.

## v1.6 - December 16 2010

* Fixed `windDirection` bug.

## v1.5 - June 29 2010

* Fixed errors in wind direction calculations.

## v1.4 - June 4 2010

* Fixed issue of a location returning multiple results.

## v1.3 - May 26 2010

* Fixed bug with trailing commas for IE.
* Added `$.getJSON()` instead of `$.ajax()`.
* Added better error message if the location is invalid.

## v1.2 - May 18 2010

* Added location param for those outside the US.
* Changed `thumbnail` to a hardcoded url.

## v1.1 - May 17 2010

* Added `thumbnail` image.

## v1.0 - May 16 2010

* Initial commit.
