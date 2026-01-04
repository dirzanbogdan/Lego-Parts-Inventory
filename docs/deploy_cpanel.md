# Deploy pe cPanel

- Creeaza baza de date MySQL si utilizator cu permisiuni.
- Seteaza variabilele de mediu in cPanel (Environment Variables sau in .htaccess): DB_HOST, DB_NAME, DB_USER, DB_PASS, BASE_URL, optional UPDATE_SECRET.
- Uploadeaza repository-ul. Seteaza document root spre [public](file:///c:/Users/bogda/Documents/trae_projects/Lego%20Parts%20Inventory/public) sau muta continutul folderului public in public_html.
- Activeaza routing cu [public/.htaccess](file:///c:/Users/bogda/Documents/trae_projects/Lego%20Parts%20Inventory/public/.htaccess).
- Initializeaza schema DB: ruleaza continutul din [database/schema.sql](file:///c:/Users/bogda/Documents/trae_projects/Lego%20Parts%20Inventory/database/schema.sql) in phpMyAdmin.
- Optional Git auto-update: acceseaza [public/update.php](file:///c:/Users/bogda/Documents/trae_projects/Lego%20Parts%20Inventory/public/update.php)?secret=VALOARE pentru a rula git pull si migrari. Daca UPDATE_SECRET nu este setat, endpoint-ul necesita rol admin logat.
- Seteaza cheile BrickLink (daca utilizezi API): BRICKLINK_CONSUMER_KEY, BRICKLINK_CONSUMER_SECRET, BRICKLINK_TOKEN, BRICKLINK_TOKEN_SECRET.
- First install: acceseaza https://lpi.e-bm.eu/install/ si completeaza formularul. Scriptul va crea env local, va rula migrarile, va crea user admin si va sterge folderul /install automat.
