# Implemented
```
mktr/api/Feed?key={RestAPI}
mktr/api/Category?key={RestAPI}
mktr/api/Brands?key={RestAPI}
mktr/api/Orders?key={RestAPI}&start_date=2000-01-01
mktr/api/Orders?key={RestAPI}&start_date=2000-01-01&page=2&limit=2
mktr/api/Cron&key={RestAPI}


/index.php?route=mktr/api/Feed&key=Alex
/index.php?route=mktr/api/Category&key=Alex
/index.php?route=mktr/api/Brands&key=Alex
/index.php?route=mktr/api/Orders&key=Alex&start_date=2000-01-01
/index.php?route=mktr/api/Cron&key={RestAPI}

/index.php?route=mktr/api/Orders&key=Alex&start_date=2000-01-01&page=2&limit=2

/index.php?route=mktr/api/CodeGenerator&key=Alex&value=50&type=0&expiration_date=2023-08-09
/index.php?route=mktr/api/CodeGenerator&key=Alex&value=50&type=1&expiration_date=2023-08-09
/index.php?route=mktr/api/CodeGenerator&key=Alex&value=50&type=2&expiration_date=2023-08-09

mktr/Api/CodeGenerator?key={RestAPI}&value=50&type=0&expiration_date=2023-08-09
mktr/Api/CodeGenerator?key={RestAPI}&value=50&type=1&expiration_date=2023-08-09
mktr/Api/CodeGenerator?key={RestAPI}&value=0&type=2&expiration_date=2023-08-09

mktr/api/Reviews?key={RestAPI}&start_date=2000-01-01

&mime-type=js
&mime-type=json
&mime-type=xml
```
# Cron Command
```shell
php {PAHT_TO_STORE}/system/library/mktr/cron.php
# OpenCart 4
php {PAHT_TO_STORE}/extension/mktr/system/library/mktr/cron.php
```

# Order reconciliation
Runs on every cron run (server cron, or Extensions > Cron Jobs on OpenCart 4 - registered
on install / when the module settings page is opened, code `mktr_reconcile`).

- order never reached theMarketer -> `save_order` + `update_order_status`
- status changed outside the admin (payment callback, courier, direct DB writes) -> `update_order_status`
- failed orders are retried up to 3 times; sent orders are tracked in `Storage/orders.json`
```
/index.php?route=mktr/api/Cron&key={RestAPI}&reconcile=1
/index.php?route=mktr/api/Cron&key={RestAPI}&reconcile=1&from=2026-01-01
```
`from` = backfill: checks every order placed since that date, 50 per call. Call again until
`backfill_done` is `1`.
# Page
&page=4

# Read Static
&read

# Download Prop
&file

