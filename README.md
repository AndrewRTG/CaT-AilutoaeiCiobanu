# CaT - Camping Info Web Tool

Aplicatie Web pentru rezervarea si gestionarea locurilor de camping.

Proiectul este organizat in arhitectura **MVC simpla**, fara framework.

## Structura MVC

- `app/Models/` - modelele, adica partea care lucreaza cu baza de date SQLite.
- `app/Controllers/` - controllerele, adica partea care primeste cereri si intoarce raspunsuri.
- `app/Views/templates/` - template-uri comune pentru header si footer.
- `app/Views/pages/` - paginile separate ale interfetei.
- `index.php` - punctul de intrare pentru interfata.
- `api/*.php` - endpoint-uri mici care apeleaza controllerele.

## Functionalitati

- Catalog campinguri.
- Pagina individuala pentru camping.
- Rezervari.
- Recenzii si mesaje cu upload foto/audio/video.
- Harta OpenStreetMap prin Leaflet.
- Modul admin pentru campinguri, rezervari, utilizatori si statistici.
- Import/export CSV si JSON.
- Export statistici SVG si raport PDF simplu.
- Login demo local pentru membru si admin.

## Tehnologii

- PHP fara framework.
- SQLite.
- HTML, CSS si JavaScript vanilla.
- Ajax prin `fetch()`.
- OpenStreetMap/Leaflet pentru harta.

## Pornire

```powershell
cd "C:\Users\Kapa\Desktop\WEB\WEB\Proiect"
& "C:\xampp\php\php.exe" -S 127.0.0.1:8000
```

Deschide:

```text
http://127.0.0.1:8000
```

## Login

Pentru testare:

- `Demo membru`
- `Demo admin`

Butoanele GitHub/Google/OpenStreetMap folosesc login demo local, ca proiectul sa ramana usor de rulat si explicat.
