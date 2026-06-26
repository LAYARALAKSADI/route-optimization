# Route Optimization - Sunquick Lanka Pvt Ltd

A complete route optimization solution for field service management that calculates the most efficient travel routes starting from Sunquick Lanka's head office.

## Project Overview

This application helps Sunquick Lanka optimize their field service routes. It takes pending job locations from an API, calculates the best route using the Nearest Neighbor algorithm, and displays the optimized visit order on an interactive map.

The system starts from Sunquick Lanka's head office coordinates (6.9271, 79.8612) and finds the most efficient sequence of job visits by calculating distances between locations using the Haversine formula.

## Features

- Route optimization using Nearest Neighbor and 2-Opt algorithms
- Interactive map with OpenStreetMap integration
- Automatic duplicate location detection and consolidation
- Distance calculation using Haversine formula
- Estimated travel time for each segment
- RESTful API for route optimization
- Responsive design for desktop and mobile
- Caching system to reduce API calls
- Rate limiting for API protection
- Unit tests for core functionality

## Technology Stack

- PHP 7.4+
- JavaScript with jQuery
- OpenStreetMap with Leaflet.js
- Bootstrap 5 for responsive UI
- PHPUnit for testing

## Installation

### Prerequisites

- PHP 7.4 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- Git (optional)

### Quick Setup

1. Clone the repository

```bash
git clone https://github.com/LAYARALAKSADI/route-optimization.git
cd route-optimization