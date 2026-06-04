# CaT - Camping Info Web Tool

CaT este o aplicatie Web pentru rezervarea, administrarea si compararea locurilor de camping. Proiectul a fost realizat pentru disciplina Tehnologii Web si respecta cerinta de a construi o aplicatie modulara, bazata pe servicii Web, fara framework-uri front-end sau back-end.

Aplicatia permite utilizatorilor sa caute campinguri, sa vada detalii, sa faca rezervari, sa lase recenzii si sa publice mesaje in comunitate, inclusiv cu fisiere multimedia. Administratorii pot gestiona campingurile, utilizatorii, rezervarile, importul/exportul de date si statisticile platformei.

## Functionalitati principale

- Catalog de campinguri cu filtrare si cautare.
- Pagina individuala pentru fiecare camping.
- Rezervari cu validare pentru perioade, capacitate si suprapuneri.
- Recenzii si comentarii din partea utilizatorilor.
- Mesaje in comunitate, inclusiv cu upload foto, audio si video.
- Harta interactiva folosind OpenStreetMap prin Leaflet.
- Comparare intre mai multe campinguri.
- Modul admin pentru campinguri, rezervari, utilizatori si statistici.
- Import si export de date in format CSV si JSON.
- Export de statistici in SVG si raport PDF.
- Feed RSS pentru noutati: campinguri, recenzii si mesaje recente.
- Autentificare locala cu email si parola.
- Autentificare prin GitHub OAuth.
- Autorizare pe roluri: membru si administrator.
- Utilizatori activi sau blocati.
- Interfata responsive pentru desktop si mobil.

## Tehnologii folosite

- PHP fara framework.
- SQLite pentru stocarea datelor.
- HTML, CSS si JavaScript vanilla.
- Ajax prin `fetch()`.
- OpenStreetMap + Leaflet pentru harta.
- GitHub OAuth pentru autentificare externa.
- Stylelint pentru validarea CSS.
- W3C Validator pentru verificarea HTML.

## Structura proiectului

```text
app/
  Controllers/       controllerele aplicatiei
  Core/              clase de baza, precum Router
  Models/            modele pentru lucrul cu baza de date
  Views/
    pages/           paginile principale ale interfetei
    templates/       header si footer

api/                 endpoint-uri Web/API
assets/
  css/               stilurile aplicatiei
  js/                codul JavaScript
auth/                fisiere pentru autentificare OAuth
config/              configurari locale
storage/             baza de date SQLite si fisiere uploadate
index.php            punctul principal de intrare in aplicatie
