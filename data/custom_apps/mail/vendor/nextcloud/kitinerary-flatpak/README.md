# kitinerary-flatpak

Flatpak adapter for the [kitinerary extractor package](https://packagist.org/packages/nextcloud/kitinerary). This package provides an adapter that invokes [kitinerary-extractor](https://github.com/KDE/itinerary) via Flatpak.

## Installation

See [the KDE itinerary wiki for the Flatpak](https://community.kde.org/KDE_PIM/KDE_Itinerary#Plasma_Mobile.2C_Flatpak) installation instructions.

```sh
composer require nextcloud/kitinerary nextcloud/kitinerary-flatpak
```

## Usage

```php
use Nextcloud\KItinerary\ItineraryExtractor;
use Nextcloud\KItinerary\Flatpak\FlatpakAdapter;
use Nextcloud\KItinerary\Exception\KItineraryRuntimeException;

$adapter = new FlatpakAdapter();
if (!$adapter->isAvailable()) {
    // ...
}
$extractor = new Extractor($adapter);

try {
    $itinerary = $extractor->extractFromString('...');
} catch (KItineraryRuntimeException $e) {
    // ...
}
```

## Installation of kitinerary-extractor flatpak

To install kitinerary-extractor from flatpak, you can use:
```
flatpak remote-add --if-not-exists kdeapps --from https://distribute.kde.org/kdeapps.flatpakrepo
flatpak install kdeapps org.kde.itinerary-extractor
```
