// Shared campus data for the "Max distance from campus" filter.
//
// This is the same idea as the campus list on the profile page: we show Dutch
// universities/campuses, filtered by the city the user picked. The difference
// is that here we also keep each campus's coordinates (latitude/longitude),
// because the distance filter needs a point to measure from.
//
// Campuses come from two places:
//   1) A built-in fallback list (always available, with coordinates).
//   2) The OpenAlex API (more campuses). OpenAlex also gives coordinates in its
//      "geo" field, which the profile page currently ignores.
//
// Other scripts use window.LetMeRentCampus (see the bottom of this file).

(function () {
  // Built-in list. Coordinates are roughly the campus/city location, which is
  // close enough for a "within a few km" search.
  var fallbackCampuses = [
    { name: 'Amsterdam University of Applied Sciences', city: 'Amsterdam', lat: 52.3676, lng: 4.9041 },
    { name: 'ArtEZ University of the Arts', city: 'Arnhem', lat: 51.9851, lng: 5.8987 },
    { name: 'Avans University of Applied Sciences', city: 'Breda', lat: 51.5719, lng: 4.7683 },
    { name: 'Breda University of Applied Sciences', city: 'Breda', lat: 51.5826, lng: 4.7752 },
    { name: 'Codarts Rotterdam', city: 'Rotterdam', lat: 51.9163, lng: 4.4790 },
    { name: 'Delft University of Technology', city: 'Delft', lat: 52.0022, lng: 4.3736 },
    { name: 'Design Academy Eindhoven', city: 'Eindhoven', lat: 51.4416, lng: 5.4791 },
    { name: 'Eindhoven University of Technology', city: 'Eindhoven', lat: 51.4480, lng: 5.4906 },
    { name: 'Erasmus University Rotterdam', city: 'Rotterdam', lat: 51.9177, lng: 4.5270 },
    { name: 'Fontys University of Applied Sciences', city: 'Eindhoven', lat: 51.4519, lng: 5.4815 },
    { name: 'Gerrit Rietveld Academie', city: 'Amsterdam', lat: 52.3389, lng: 4.8540 },
    { name: 'Hanze University of Applied Sciences', city: 'Groningen', lat: 53.2376, lng: 6.5263 },
    { name: 'Hotelschool The Hague', city: 'Den Haag', lat: 52.0998, lng: 4.2740 },
    { name: 'Inholland University of Applied Sciences', city: 'Amsterdam', lat: 52.3120, lng: 4.9430 },
    { name: 'Leiden University', city: 'Leiden', lat: 52.1571, lng: 4.4850 },
    { name: 'Maastricht University', city: 'Maastricht', lat: 50.8485, lng: 5.6880 },
    { name: 'NHL Stenden University of Applied Sciences - Emmen', city: 'Emmen', lat: 52.7784282, lng: 6.9108822 },
    { name: 'NHL Stenden University of Applied Sciences', city: 'Leeuwarden', lat: 53.1960, lng: 5.7960 },
    { name: 'Radboud University', city: 'Nijmegen', lat: 51.8190, lng: 5.8657 },
    { name: 'Rotterdam University of Applied Sciences', city: 'Rotterdam', lat: 51.9170, lng: 4.4840 },
    { name: 'Saxion University of Applied Sciences', city: 'Enschede', lat: 52.2215, lng: 6.8937 },
    { name: 'The Hague University of Applied Sciences', city: 'Den Haag', lat: 52.0660, lng: 4.3250 },
    { name: 'Tilburg University', city: 'Tilburg', lat: 51.5640, lng: 5.0440 },
    { name: 'University of Amsterdam', city: 'Amsterdam', lat: 52.3560, lng: 4.9560 },
    { name: 'University of Groningen', city: 'Groningen', lat: 53.2190, lng: 6.5630 },
    { name: 'University of Twente', city: 'Enschede', lat: 52.2390, lng: 6.8560 },
    { name: 'Utrecht University', city: 'Utrecht', lat: 52.0855, lng: 5.1700 },
    { name: 'Vrije Universiteit Amsterdam', city: 'Amsterdam', lat: 52.3340, lng: 4.8650 },
    { name: 'Wageningen University & Research', city: 'Wageningen', lat: 51.9870, lng: 5.6650 },
    { name: 'Windesheim University of Applied Sciences', city: 'Zwolle', lat: 52.4980, lng: 6.0790 },
    { name: 'Zuyd University of Applied Sciences', city: 'Maastricht', lat: 50.8480, lng: 5.6900 }
  ];

  // A couple of cities are written in different ways. We map them to one form
  // so "The Hague" and "Den Haag" match the same campuses.
  var cityAliases = {
    'the hague': 'den haag',
    'den haag': 'den haag',
    "'s-gravenhage": 'den haag',
    's-gravenhage': 'den haag',
    's gravenhage': 'den haag'
  };

  var openAlexEndpoint =
    'https://api.openalex.org/institutions?filter=country_code:NL,type:education&per-page=200&select=display_name,geo';

  // The list we actually use. It starts as the fallback and grows once OpenAlex
  // has loaded.
  var campuses = fallbackCampuses.slice();
  var openAlexLoaded = false;

  function normalizeCity(city) {
    if (!city) {
      return '';
    }

    var normalized = city.toLowerCase();
    // Remove accents (so "Nijmegen" matches no matter how it is typed).
    normalized = normalized.normalize('NFD').replace(/[̀-ͯ]/g, '');
    // Collapse repeated spaces and trim the ends.
    normalized = normalized.replace(/\s+/g, ' ').trim();

    if (cityAliases[normalized]) {
      return cityAliases[normalized];
    }
    return normalized;
  }

  // Remove duplicates (same name + city) and sort by city, then name.
  function uniqueCampuses(items) {
    var seen = {};
    var result = [];

    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      var name = (item.name || '').trim();
      var city = (item.city || '').trim();

      if (name === '') {
        continue;
      }

      var key = name.toLowerCase() + '|' + city.toLowerCase();
      if (seen[key]) {
        continue;
      }
      seen[key] = true;
      result.push({ name: name, city: city, lat: item.lat, lng: item.lng });
    }

    result.sort(function (left, right) {
      var cityCompare = left.city.localeCompare(right.city, 'nl', { sensitivity: 'base' });
      if (cityCompare !== 0) {
        return cityCompare;
      }
      return left.name.localeCompare(right.name, 'nl', { sensitivity: 'base' });
    });

    return result;
  }

  // Campuses that match a city. When no city is given we return all of them.
  function forCity(cityName) {
    var wanted = normalizeCity(cityName);
    var all = uniqueCampuses(campuses);

    if (wanted === '') {
      return all;
    }

    var matches = [];
    for (var i = 0; i < all.length; i++) {
      if (normalizeCity(all[i].city) === wanted) {
        matches.push(all[i]);
      }
    }
    return matches;
  }

  // Find one campus by its name (optionally inside a city).
  function findByName(name, cityName) {
    var list = forCity(cityName);
    for (var i = 0; i < list.length; i++) {
      if (list[i].name === name) {
        return list[i];
      }
    }

    // Not found inside the city: try the whole list as a fallback.
    var all = uniqueCampuses(campuses);
    for (var j = 0; j < all.length; j++) {
      if (all[j].name === name) {
        return all[j];
      }
    }
    return null;
  }

  // Load extra campuses from OpenAlex once. We keep their coordinates this time.
  function load(callback) {
    // The fallback list is ready right away.
    if (typeof callback === 'function') {
      callback();
    }

    if (openAlexLoaded) {
      return;
    }
    openAlexLoaded = true;

    fetch(openAlexEndpoint)
      .then(function (response) {
        if (!response.ok) {
          throw new Error('OpenAlex request failed with ' + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        var results = data.results || [];
        var apiCampuses = [];

        for (var i = 0; i < results.length; i++) {
          var institution = results[i];
          var geo = institution.geo || {};

          var lat = null;
          var lng = null;
          if (typeof geo.latitude === 'number') {
            lat = geo.latitude;
          }
          if (typeof geo.longitude === 'number') {
            lng = geo.longitude;
          }

          apiCampuses.push({
            name: institution.display_name || '',
            city: geo.city || '',
            lat: lat,
            lng: lng
          });
        }

        campuses = uniqueCampuses(fallbackCampuses.concat(apiCampuses));

        if (typeof callback === 'function') {
          callback();
        }
      })
      .catch(function () {
        // If OpenAlex fails we simply keep the fallback list.
      });
  }

  // Make the helpers available to the page scripts.
  window.LetMeRentCampus = {
    normalizeCity: normalizeCity,
    forCity: forCity,
    findByName: findByName,
    load: load
  };
})();
