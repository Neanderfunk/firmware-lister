# firmware-lister

Der Verzeichnis-Lister, mit dem die Firmwareserver von Freifunk Neanderland
ihre Images anzeigen: eine PHP-Datei, die ein Verzeichnis als sortierbare,
durchsuchbare Tabelle darstellt.

## Autor

Geschrieben von **Alexander ([@alex1702](https://github.com/alex1702))**.
Der Ausgangsstand in diesem Repo ist seine Fassung vom 11.09.2020, unverändert
als erster Commit festgehalten (`0c08e35`). Alles danach sind Änderungen
daran, einzeln begründet in der Git-Historie.

## Wo das läuft

| Host | Webroot |
| --- | --- |
| `firmware.ffnef.de` | `/var/www/download.ffnef.de/` |
| `imageslive.ffdus.de` (wir-horst) | `/var/www/html/images/` |

Beide liefen bis zum 06.10.2026 mit byte-gleichen Kopien, die von Hand
gepflegt wurden. Dieses Repo gibt es, damit eine Änderung einmal gemacht wird
und nicht je Server einmal.

## Was hierher gehört, und was nicht

Im Repo liegt die **Software**: `list.php` und die Oberfläche
(`css/`, `js/`, `fonts/`, `img/`).

Auf dem Server bleibt, was den einzelnen Host ausmacht und hier nichts zu
suchen hat:

* die Symlinks auf die Image-Verzeichnisse (`images2025.1` und so weiter,
  die nach `/home/build/...` zeigen),
* die nginx-Konfiguration,
* die ausgelieferten Inhalte selbst.

Ein `git pull` darf diese Dinge nie anfassen. Deshalb wird hier auch nichts
ausgerollt, was Verzeichnisse anlegt oder löscht.

## Einspielen

```sh
cd <webroot>
curl -fsSL -o /tmp/list.php.neu \
  https://raw.githubusercontent.com/Neanderfunk/firmware-lister/main/list.php
php -l /tmp/list.php.neu
install -o www-data -g www-data -m 644 /tmp/list.php.neu list.php
```

Die Syntaxprüfung vor dem Tauschen ist kein Zierrat: Ohne sie kann im
Webroot kurzzeitig eine halbe Datei liegen.

**Keine Sicherungskopie im Webroot ablegen.** Eine Datei namens
`list.php.vor-404` endet nicht auf `.php`, fällt damit nicht unter
`location ~ \.php$` und wird vom Webserver als Quelltext ausgeliefert. Genau
das ist am 06.10.2026 passiert. Die alte Fassung steht ohnehin in diesem
Repo; zum Zurückrollen genügt der erste Commit.

## Was sich seit 2020 geändert hat

**Fehlende Pfade melden 404** (`fe98686`, `58a13e1`). Vorher reichte
`list.php` den angefragten Pfad an `scandir()` weiter; gab es ihn nicht, kam
eine leere Liste heraus und PHP meldete HTTP 200. Wer die Vollständigkeit
eines Verzeichnisses über Statuscodes prüfte, bekam "liegt alles da" für
etwas, das es nicht gab. Jetzt kommt 404, und die Seite wird trotzdem
angezeigt, mit einem Hinweis statt einer leeren Tabelle.

Direkt bei der Umstellung kam dadurch zutage, dass auf `imageslive` drei der
sechs `images*`-Symlinks ins Leere zeigten. Sie hatten seit dem Wegräumen der
zugehörigen Buildbäume leere Verzeichnisse mit 200 ausgeliefert.

**Der Pfad wird geprüft** (gleicher Commit). Er ging vorher ungeprüft ins
Dateisystem. Dass sich daraus nichts ausbrechen ließ, lag allein am
Webserver: nginx weist `/../` mit 400 ab, bevor PHP gefragt wird. Unter PHPs
eingebautem Server, auf den der Dateikopf ausdrücklich eingeht, listete der
alte Stand das Elternverzeichnis auf. Die Prüfung normalisiert den Pfad jetzt
rein rechnerisch; **`realpath()` wäre hier falsch**, weil es die
beabsichtigten Symlinks nach außen auflöst und damit jedes Image-Verzeichnis
zu 404 machen würde. Das ist einmal passiert und in `58a13e1` korrigiert.

Dazu zwei Kleinigkeiten: Der Query-String landete im Dateipfad (`/?foo=bar`
war ein Verzeichnisname), und Pfad wie Dateinamen gingen unmaskiert in die
Ausgabe.

## Mitgelieferte Fremdbestandteile

| Teil | Version | Stand |
| --- | --- | --- |
| jQuery (slim) | 3.5.1 | 2020 |
| Bootstrap | 4.5.0 | 2020 |
| DataTables (bs4) | 1.10.21 | 2020 |
| Bootstrap Icons | 1030 SVGs in `img/` | 2020 |

Alles aus dem Jahr 2020 und seitdem nicht angefasst. Vor einer Aktualisierung
lohnt der Blick, ob die jeweiligen Projekte seitdem Sicherheitsmeldungen
hatten; ein Sprung auf Bootstrap 5 würde die Vorlage in `list.php` ändern und
gehört deshalb in einen eigenen Commit.

## Lizenz

**Offen.** Die Datei trug keinen Lizenzhinweis, und sie ist nicht unsere
Arbeit. Das klärt Alexander ([@alex1702](https://github.com/alex1702)),
bevor hier etwas hineingeschrieben wird. Die mitgelieferten Fremdteile stehen
unter ihren eigenen Lizenzen (jQuery, Bootstrap und Bootstrap Icons jeweils
MIT, DataTables MIT).

## Prüfen

Ohne Webserver, gegen einen Testbaum:

```sh
php -S 127.0.0.1:8080 list.php
```

Ein Testbaum sollte **einen Symlink enthalten, der aus dem Verzeichnis
herauszeigt** - sonst entgeht genau der Fall, der die Image-Verzeichnisse
ausmacht. Erwartet wird: vorhandenes Verzeichnis 200, leeres Verzeichnis 200,
fehlendes 404, `/../` bleibt auf der Wurzel, `/?foo=bar` 200.
