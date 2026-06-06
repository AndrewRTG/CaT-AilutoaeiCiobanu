# CaT - Camping Info Web Tool

CaT este o aplicatie Web pentru rezervarea, administrarea si compararea locurilor de camping. Proiectul a fost realizat pentru disciplina Tehnologii Web si respecta cerinta de a construi o aplicatie modulara, bazata pe servicii Web, fara framework-uri front-end sau back-end.

Aplicatia permite utilizatorilor sa caute campinguri, sa vada detalii, sa faca rezervari, sa lase recenzii si sa publice mesaje in comunitate, inclusiv cu fisiere multimedia. Administratorii pot gestiona campingurile, utilizatorii, rezervarile, rolurile, importul/exportul de date si statisticile platformei.

## Functionalitati principale

- Catalog de campinguri cu filtrare si cautare.
- Pagina individuala pentru fiecare camping.
- Rezervari cu validare pentru perioade, capacitate si suprapuneri.
- Recenzii si comentarii din partea utilizatorilor.
- Mesaje in comunitate, inclusiv cu upload foto, audio si video.
- Harta interactiva folosind OpenStreetMap prin Leaflet.
- Comparare intre mai multe campinguri.
- Modul admin pentru campinguri, rezervari, utilizatori, roluri si statistici.
- Roluri custom pentru utilizatori.
- Permisiuni administrative configurabile pentru fiecare rol.
- Control acces pe baza de permisiuni pentru campinguri, rezervari, utilizatori, roluri, statistici si import/export.
- Import si export de date in format CSV si JSON.
- Export de statistici in SVG si raport PDF.
- Feed RSS pentru noutati: campinguri, recenzii si mesaje recente.
- Autentificare locala cu email si parola.
- Autentificare prin GitHub OAuth.
- Autorizare pe roluri si permisiuni.
- Utilizatori activi sau blocati.
- Interfata responsive pentru desktop si mobil.

## Roluri si permisiuni

Aplicatia foloseste un sistem simplu de tip RBAC (Role-Based Access Control). Fiecare utilizator are un rol, iar fiecare rol poate avea una sau mai multe permisiuni administrative.

Rolurile de baza sunt:

- `admin` - are acces complet la toate functionalitatile administrative.
- `member` - utilizator obisnuit, poate face rezervari, recenzii si mesaje.
- `moderator` - rol de sistem pregatit pentru moderarea continutului.
- roluri custom - roluri create din panoul admin, cu permisiuni alese manual.

Permisiunile disponibile sunt:

- `manage_campings` - permite adaugarea, editarea si stergerea campingurilor.
- `manage_reservations` - permite vizualizarea si modificarea statusului rezervarilor.
- `manage_users` - permite gestionarea utilizatorilor.
- `view_stats` - permite accesul la dashboard-ul cu statistici.
- `import_export` - permite importul si exportul de date.
- `manage_roles` - permite crearea si stergerea rolurilor custom.
- `moderate_messages` - permisiune pregatita pentru moderarea mesajelor si recenziilor.

Exemplu: un rol custom numit `Manager rezervari` poate primi doar permisiunea `manage_reservations`. In acest caz, utilizatorul vede in admin doar sectiunea de rezervari si nu poate accesa campinguri, utilizatori, roluri sau statistici.

Verificarea permisiunilor se face atat in interfata, unde tab-urile fara permisiune sunt ascunse, cat si pe server, prin functia `require_permission()`.

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
docs/                raportul Scholarly HTML
index.php            punctul principal de intrare in aplicatie
