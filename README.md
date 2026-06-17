# LetMeRent - Frontend

The web frontend for **LetMeRent**, a service that collects Dutch student-housing
listings from several rental websites and shows them in one place. This repository
is the **website only**. Its a server-rendered PHP application. It reads listings and
user data from separate backend services over HTTP. It does not contain a database
or the scrapers itself.

> Part of the wider LetMeRent system: `LetMeRent-Scraper` (Python/Scrapy), a `Chrono` preferences/scheduling service, and a MongoDB store. Those live
> in their own repositories.

## Overview

LetMeRent-Frontend lets a visitor:

- **Browse** student-housing listings as a card grid, with search (city, max
  budget, move-in date) and filters (source website, price sort, rooms, energy
  label, furnished/housemates/plot tags, garages-only).
- **View listings on a map** (Google Maps), including a draw-a-circle area filter,
  and a "search a place" tool.
- **Open a listing detail page** with an image gallery, description,
  tags, a location map and a link back to the original rental site.
- **Sign up / sign in** (JWT-based) and manage a **profile** of search and alert
  **preferences**, which are sent to the Chrono service.


## Architecture

The app is a set of PHP entry-point pages. Each page pulls in small partials from
`components/` and shared helpers from `includes/`, then renders HTML. Client-side
behaviour (filters, search fields, maps) is layered on with separate JS files.

### External dependencies

| Dependency | Used for | Configured by |
|---|---|---|
| Listings API (`/data`) | Fetching and filtering listings | `API_URL` |
| Auth API | Register / login / `/me`, JWT sessions | `AUTH_API_BASE_URL` |
| Chrono API | Saving & loading scrape/alert preferences | `CHRONO_API_BASE_URL` |
| Google Maps JavaScript API | Map view + detail-page map | `GOOGLE_MAPS_API_KEY` |
| PDOK Locatieserver | City name autocomplete (client-side) | (public API) |
| OpenAlex | University/campus list on the profile page (client-side) | (public API) |

### Shared layer (`includes/`)

Every page reads configuration and talks to the API the same way through these
helpers:

- `env.php` - `readEnv($key)`: reads a value from `.env` (environment variables
  override the file).
- `api.php` - `fetchFromApi($params)`: one request to the listings API; always
  returns `['error' => …]` or `['data' => […], 'count' => N]`.
- `listingTags.php` - `buildListingTags($listing)`: the single source of the
  tags shown on cards, map items, map popups and the detail page.
- `availabilityFormat.php` - `formatAvailability($value)`: normalises the many
  move-in-date formats into readable text.
- `nav.php` - the shared top navigation bar (pages set `$navBase`, `$navActive`,
  etc. before including it).

### Data flow

**Browse (`index.php`)**

```
$_GET filters → components/listings/listings.php (builds params)
            → includes/api.php fetchFromApi() → Listings API /data
            → renderCard.php renders each card → grid + pagination
```

**Map (`map/map.php`)**

```
components/map/mapPageData.php → mapListings.php (fetches all matching listings)
            → mapMarkers.php builds marker data → JSON → map/map.js (Google Maps)
            → renderMapSidebar.php / renderMapListItem.php render the sidebar list
```

**Detail (`detail/detail.php`)**

```
?id=… → detailHelpers.php findListingById() (API lookup)
     → detailData.php (prepares view variables) → detail.php template
     → detail.js (gallery popup + Google map)
```

**Profile & auth (`profile/`, `sign-up-in/`)**

```
sign-up-in/auth.js → login.php / register.php → callAuthApi() → Auth API
     → tokens stored in PHP session + localStorage
profile.php → callApiWithAuth('/me') (Auth API) + callChronoApi('/chrono/tasks') (Chrono API)
```

## Project structure

```
LetMeRent-FrontEnd/
├── index.php                 # Browse page (entry point)
├── styles.css                # Shared/global styles (nav, layout)
├── index.css                 # Browse-page styles
├── includes/                 # Shared PHP helpers used by every page
│   ├── env.php               #   read values from .env
│   ├── api.php               #   single listings-API client (fetchFromApi)
│   ├── listingTags.php       #   build the feature chips for a listing
│   ├── availabilityFormat.php#   format move-in dates
│   └── nav.php               #   shared top navigation bar
├── components/               # Page partials, grouped by feature
│   ├── indexPage/            #   search hero, filter bar, results bar + their JS
│   ├── filters/              #   filter chips/dropdowns (PHP) + filter JS
│   ├── listings/             #   listings query, card renderer, grid, pagination
│   └── map/                  #   all map PHP + JS (data, markers, sidebar, tools)
├── map/                      # Map View page + its CSS
│   ├── map.php
│   └── map.css
├── detail/                   # Listing detail page
│   ├── detail.php            #   entry + HTML template
│   ├── detailData.php        #   builds the view variables
│   ├── detailHelpers.php     #   API lookup + formatting helpers
│   ├── detail.js             #   gallery popup + location map
│   └── detail.css
├── profile/                  # Profile & preferences page (login required)
│   ├── profile.php           #   server logic + page markup
│   ├── profile.js            #   slider, source dropdown, city/campus autocomplete
│   └── profile.css
├── sign-up-in/               # Authentication
│   ├── signin.php / signup.php   # forms (HTML)
│   ├── login.php / register.php  # POST handlers → Auth API
│   ├── logout.php
│   ├── authConfig.php        #   env, session, redirect/JSON helpers
│   ├── authApi.php           #   Auth API calls, token storage, auth headers
│   ├── auth.js               #   submits the forms via fetch, stores tokens
│   └── signupin.css
├── img/                      # Static images (placeholder apartment)
├── Dockerfile                # PHP 8.3 CLI + curl, runs the built-in server
├── docker-compose.yml        # Mounts the code as a volume on APP_PORT
├── .env.example              # Template for the required environment variables
└── .dockerignore
```


## Listings API parameters

`components/listings/listings.php` builds these query parameters and sends them to
`API_URL`. The API performs all filtering, sorting and pagination.

| Parameter | Example | Meaning |
|---|---|---|
| `city` | `?city=groningen` | Only this city (case-insensitive). |
| `source` | `?source=funda,kamernet` | One or more source websites (comma-separated). |
| `min_price` / `max_price` | `?max_price=1200` | Price range (a max of `5000` means "no upper limit"). |
| `min_rooms` / `max_rooms` | `?min_rooms=3` | Room-count range. |
| `has` | `?has=furnished,plot_size` | Only listings that have these fields. |
| `energy_label` | `?energy_label=A` | Energy class (matched by first letter). |
| `no_living_area` | `?no_living_area=1` | Only listings without a living area (garages/parking). |
| `available_by` | `?available_by=2026-09-01` | Move-in date. |
| `sort` / `order` | `?sort=price&order=asc` | Sort field and direction (`asc`/`desc`). |
| `limit` / `skip` | `?limit=12&skip=12` | Page size and offset (browse page uses 12). |