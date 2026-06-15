<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sir Francis Sale Flyer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{
      --cb-purple:#28364B;
      --cb-purple-dark:#172235;
      --cb-yellow:#CEBD88;
      --cb-yellow-soft:#E7D8A6;
      --cb-cream:#ffffff;
      --cb-ink:#211526;
      --cb-muted:#74667a;
    }
    *{box-sizing:border-box}
    body{margin:0;background:#f4f2f7;color:var(--cb-ink);font-family:Arial,Calibri,Helvetica,sans-serif}
    .screen-toolbar{position:sticky;top:0;z-index:50;background:var(--cb-purple-dark);color:white;padding:10px;text-align:center;font-size:13px}
    .screen-toolbar button{border:0;border-radius:999px;background:var(--cb-yellow);color:var(--cb-purple-dark);font-weight:800;padding:8px 16px;margin-right:10px}
    .flyer{width:210mm;min-height:297mm;margin:18px auto;background:#ffffff;box-shadow:0 25px 70px rgba(42,0,77,.22);overflow:hidden}
    .hero{background:linear-gradient(135deg,#172235 0%,#28364B 58%,#3B4B63 100%);color:white;border-bottom:10px solid var(--cb-yellow)}
    .brand{font-size:22px;font-weight:900;letter-spacing:.03em;color:var(--cb-yellow)}
    .validity{display:inline-block;border:2px solid var(--cb-yellow);border-radius:999px;color:var(--cb-yellow);padding:7px 14px;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;white-space:normal;text-align:right;background:rgba(255,176,0,.08)}
    h1{font-size:46px;line-height:.94;font-weight:900;letter-spacing:-1.8px;margin:0;max-width:520px}
    .hero-copy{font-size:13px;line-height:1.45;color:rgba(255,255,255,.86);max-width:270px;border-left:3px solid var(--cb-yellow);padding-left:18px}
    .section-title{font-size:25px;font-weight:900;color:var(--cb-purple);letter-spacing:-.7px;margin:0}
    .category-section{margin-bottom:30px;break-inside:avoid}
    .category-banner{border-radius:18px;background:linear-gradient(135deg,var(--cb-purple-dark),var(--cb-purple));color:white;padding:14px 18px;margin-bottom:14px;border-left:8px solid var(--cb-yellow);box-shadow:0 10px 24px rgba(42,0,77,.16)}
    .category-name{font-size:19px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;line-height:1;color:var(--cb-yellow)}
    .category-desc{font-size:11px;font-weight:700;color:rgba(255,255,255,.86);margin-top:6px;line-height:1.35}
    .product-card{height:100%;border:0;border-radius:18px;box-shadow:0 10px 26px rgba(50,20,59,.09);overflow:hidden;background:white}
    .product-img-box{height:148px;background:#f3f0f8;display:flex;align-items:stretch;justify-content:stretch;padding:0;border-bottom:1px solid rgba(75,0,130,.10);overflow:hidden}
    .product-img-box img{width:100%;height:100%;object-fit:cover;display:block}
    .fallback{width:100%;height:100%;background:linear-gradient(135deg,rgba(75,0,130,.12),rgba(255,242,0,.28));display:flex;align-items:center;justify-content:center;color:rgba(75,0,130,.55);font-size:24px;font-weight:900}
    .product-title{font-size:12px;line-height:1.12;font-weight:900;color:var(--cb-purple-dark);min-height:28px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .product-meta{font-size:8px;color:var(--cb-muted);font-weight:700;min-height:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .offer-row{border-top:1px solid rgba(50,20,59,.10);padding-top:6px;margin-top:6px}
    .offer-row:first-child{border-top:0;margin-top:0;padding-top:0}
    .sale-price{font-size:30px;line-height:.88;font-weight:900;color:var(--cb-purple);letter-spacing:-1.4px;white-space:nowrap}
    .sale-size{font-size:10px;font-weight:900;color:var(--cb-purple);white-space:nowrap}
    .was-price{font-size:9px;font-weight:900;color:#817286;text-decoration-line:line-through;text-decoration-thickness:2px;text-decoration-color:var(--cb-purple)}
    .save-text{font-size:9px;font-weight:900;color:#ff7a00;text-transform:uppercase;line-height:1.05}
    .clearance-wrap{background:linear-gradient(135deg,#172235 0%,#28364B 70%,#3B4B63 100%);color:white;margin-top:28px;border-top:10px solid var(--cb-yellow)}
    .clearance-wrap .section-title{color:white}
    .bottom-validity{border-top:1px solid rgba(50,20,59,.14);font-size:10px;color:var(--cb-muted);font-weight:700}
    .empty,.error{border:2px dashed rgba(50,20,59,.18);border-radius:18px;background:white;color:var(--cb-muted);font-weight:800;padding:28px;text-align:center}
    @media print{
  body{background:white}
  .screen-toolbar{display:none}

  @page{
    size:A4;
    margin:8mm;
  }

  .flyer{
    width:auto;
    min-height:auto;
    margin:0;
    box-shadow:none;
    overflow:visible;
  }

  .hero{
    padding:10mm !important;
    break-after:avoid;
  }

  section.p-4,
  .clearance-wrap{
    padding:7mm 0 !important;
    margin:0 !important;
  }

  .category-section{
    margin-bottom:8mm;
    break-inside:auto;
  }

  .category-banner{
    padding:8px 12px;
    margin-bottom:4mm;
    break-after:avoid;
  }

  .row{
    display:grid !important;
    grid-template-columns:repeat(3, 1fr);
    gap:4mm !important;
    margin:0 !important;
  }

  .row > .col{
    width:auto !important;
    max-width:none !important;
    padding:0 !important;
    break-inside:avoid;
  }

  .product-card{
    break-inside:avoid;
    box-shadow:none;
    border:1px solid rgba(50,20,59,.16);
  }

  .product-img-box{
    height:35mm;
  }

  .sale-price{
    font-size:24px;
  }

  .bottom-validity{
    break-inside:avoid;
    margin-top:6mm !important;
  }
}
    @media screen and (max-width:900px){.flyer{transform:scale(.72);transform-origin:top center;margin-bottom:-80mm}}
  </style>
</head>
<body>
  <div class="screen-toolbar"><button onclick="downloadPDF()">Print / Save PDF</button><span id="status">Loading live specials…</span></div>

  <main class="flyer">
    <header class="hero p-4 p-md-5">
      <div class="d-flex justify-content-between gap-3 align-items-start mb-4">
        <div class="brand">Sir Francis</div>
        <div class="validity" id="validityTop">Sale valid between today till while stocks last</div>
      </div>
      <div class="row g-4 align-items-end">
        <div class="col-8"><div class="text-uppercase fw-bold mb-2" style="color:var(--cb-yellow);letter-spacing:.22em;font-size:10px">Product brochure</div><h1>Marine collagen, fish gelatine and specialist supply.</h1></div>
        <div class="col-4"><div class="hero-copy">Limited-time offers on selected Sir Francis favourites.</div></div>
      </div>
    </header>

    <section class="p-4">
      <div class="d-flex align-items-end justify-content-between mb-3"><h2 class="section-title">Current Specials</h2></div>
      <div id="specials"></div>
      <div class="bottom-validity d-flex justify-content-between gap-3 mt-4 pt-3"><div><div id="validityBottom">Sale valid between today till while stocks last</div><div style="margin-top:6px;font-size:9px;line-height:1.35">If any errors are spotted, email <strong>info@fishgelatine.co.za</strong> or WhatsApp <strong>084 231 9326</strong> and qualify for a 20% discount coupon.</div></div><span>www.fishgelatine.co.za</span></div>
    </section>

    <section class="clearance-wrap p-4">
      <h2 class="section-title mb-2">Clearance</h2>
      <div class="mb-3" style="font-size:12px;font-weight:800;color:rgba(255,255,255,.86);line-height:1.35">Items that are defective or closing in on sell-by date. These are sold to go as is.</div>
      <div id="clearance" class="row row-cols-3 g-3"></div>
    </section>


  </main>

  <script>
    const PRODUCT_TSV = "https://docs.google.com/spreadsheets/d/e/2PACX-1vRhtg-QlUDokG6Tcsj29r1RMRWp9y9Fl2rcjh17s5F3xc5Re6tfaU54imMepBWNbA1xJKoVvNCUOX2d/pub?gid=380423212&single=true&output=tsv";
    const CLEARANCE_TSV = "https://docs.google.com/spreadsheets/d/e/2PACX-1vSvZQRweWXcY9ap_m_wZnut2KpFF-Y7Kcvwh9AutZwdB7H768y0bZZhZfdXo28L6740zYbRTA-K2da-/pub?gid=0&single=true&output=tsv";
    const SITE_ROOT = "https://www.fishgelatine.co.za/v2";
    const $ = id => document.getElementById(id);
    const chr = n => String.fromCharCode(n);
    const key = value => String(value || "").trim().toLowerCase().split(" ").join("_").split("-").join("_").split(".").join("_");

    function parseTSV(text,startAtRow3){
      const rows=text.replaceAll(chr(13),"").split(chr(10)).filter(Boolean).map(line=>line.split(chr(9)));
      const headers=rows[0].map(key);
      const dataRows=rows.slice(startAtRow3?2:1);
      return dataRows.filter(row=>row.some(cell=>String(cell).trim())).map(row=>Object.fromEntries(headers.map((header,i)=>[header,row[i]?row[i].trim():""])));
    }
    async function loadProducts(){const response=await fetch(PRODUCT_TSV,{cache:"no-store"});return parseTSV(await response.text(),true)}
    async function loadClearance(){const response=await fetch(CLEARANCE_TSV,{cache:"no-store"});return parseTSV(await response.text(),false)}
    function first(row,names){for(const name of names){const value=row[key(name)];if(value!==undefined&&String(value).trim()!=="")return String(value).trim()}return ""}
    function numberFrom(value){const cleaned=String(value||"").replaceAll(",",".").split("R").join("").split(" ").join("");const numeric=Number(cleaned.replaceAll("%",""));return Number.isFinite(numeric)?numeric:NaN}
    function money(value){const n=numberFrom(value);return Number.isFinite(n)?`R${n.toFixed(n%1?2:0)}`:(value||"")}
    function getId(row){return first(row,["id","product_id","sku","code","item_id","variant_id"])}
    function getTitle(row){return first(row,["title","name","product_name","description","item","product"])}
    function getSize(row){return first(row,["size","weight","pack_size","variant","option1","grams","volume"])}
    function getCategory(row){return first(row,["parent_category"])||"More Specials"}
    function getBrand(row){return first(row,["brand","collection"])}
    function getOriginalPrice(row){return first(row,["original_price","was_price","regular_price","rrp","retail_price","price"])}
    function getDiscountAmount(row){const raw=first(row,["discount"]);if(!raw)return NaN;const n=numberFrom(raw);return Number.isFinite(n)?n:NaN}
    function getDealPrice(row){const explicit=first(row,["discounted_price","sale_price","special_price","promo_price"]);if(explicit)return explicit;const original=numberFrom(getOriginalPrice(row));const discountRaw=first(row,["discount"]);const discount=numberFrom(discountRaw);if(Number.isFinite(original)&&Number.isFinite(discount)&&discount>0){if(discountRaw.includes("%"))return String(original-(original*discount/100));return String(original-discount)}return getOriginalPrice(row)}
    function parseDateValue(value){
      if(!value)return null;
      const raw=String(value).trim();
      if(!raw)return null;
      const clean=raw.split(" ")[0].trim();
      let date=null;
      const iso=clean.match(/^([0-9]{4})[-/]([0-9]{1,2})[-/]([0-9]{1,2})$/);
      if(iso){date=new Date(Number(iso[1]),Number(iso[2])-1,Number(iso[3]));}
      const dmy=clean.match(/^([0-9]{1,2})[-/]([0-9]{1,2})[-/]([0-9]{2,4})$/);
      if(!date&&dmy){let year=Number(dmy[3]);if(year<100)year+=2000;date=new Date(year,Number(dmy[2])-1,Number(dmy[1]));}
      if(!date){date=new Date(raw);}
      if(Number.isNaN(date.getTime()))return null;
      date.setHours(0,0,0,0);
      return date;
    }
    function hasDiscount(row){if(!getId(row))return false;const discounted=first(row,["discounted_price"]);const discount=first(row,["discount"]);const discountedNumber=numberFrom(discounted);const discountNumber=numberFrom(discount);const originalNumber=numberFrom(getOriginalPrice(row));const dealNumber=numberFrom(getDealPrice(row));const hasDiscountedPrice=discounted!==""&&Number.isFinite(discountedNumber)&&discountedNumber>0&&Number.isFinite(originalNumber)&&discountedNumber<originalNumber;const hasDiscountValue=discount!==""&&discount.toLowerCase()!=="0"&&discount.toLowerCase()!=="0%"&&Number.isFinite(discountNumber)&&discountNumber>0&&Number.isFinite(dealNumber)&&Number.isFinite(originalNumber)&&dealNumber<originalNumber;const today=new Date();today.setHours(0,0,0,0);const validFrom=parseDateValue(first(row,["discount_valid_from","valid_from","sale_valid_from"]));const validUntil=parseDateValue(first(row,["discount_valid_until","valid_until","sale_valid_until"]));const started=!validFrom||validFrom.getTime()<=today.getTime();const notExpired=!validUntil||validUntil.getTime()>=today.getTime();return (hasDiscountedPrice||hasDiscountValue)&&started&&notExpired}
    function cleanUrl(url){let value=String(url||"").trim();if(!value)return"";if(value.startsWith("//"))value=`https:${value}`;if(value.startsWith("/"))value=`${SITE_ROOT}${value}`;return value}
    function imageCandidates(row){const raw=first(row,["img_url","image_url","image","images","thumbnail","photo_url"]);return raw.split(",").map(cleanUrl).filter(Boolean)}
    function escapeHTML(value){return String(value).replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;")}
    function escapeAttr(value){return escapeHTML(value).replaceAll("'","&#39;")}
    function imageHTML(product,title){const urls=imageCandidates(product);if(!urls.length)return`<div class="product-img-box"><div class="fallback">CB</div></div>`;return`<div class="product-img-box"><img src="${escapeAttr(urls[0])}" alt="${escapeAttr(title)}" data-urls='${escapeAttr(JSON.stringify(urls))}' data-index="0" onerror="nextImage(this)"></div>`}
    window.nextImage=function(img){let urls=[];try{urls=JSON.parse(img.dataset.urls||"[]")}catch(e){}const next=Number(img.dataset.index||0)+1;if(next<urls.length){img.dataset.index=String(next);img.src=urls[next]}else{img.parentElement.innerHTML=`<div class="fallback">CB</div>`}}
    function saveText(row,dealOverride){const explicit=first(row,["discount","saving","save"]);const original=numberFrom(getOriginalPrice(row));const deal=numberFrom(dealOverride||getDealPrice(row));if(Number.isFinite(original)&&Number.isFinite(deal)&&original>deal){const percentSave=Math.round(((original-deal)/original)*100);return `Save ${percentSave}%`}if(explicit){const n=numberFrom(explicit);return Number.isFinite(n)&&n>0?`Save ${Math.round(n)}%`:""}return ""}
    function prettyDate(date){return date.toLocaleDateString("en-ZA",{day:"numeric",month:"long",year:"numeric"})}
    function dateKey(date){return date?date.toISOString().slice(0,10):""}
    function mainUntilDate(products){
      const dates=products.map(p=>parseDateValue(first(p,["discount_valid_until","valid_until","sale_valid_until"]))).filter(Boolean);
      if(!dates.length)return null;
      const counts=new Map();
      dates.forEach(date=>{const k=dateKey(date);counts.set(k,(counts.get(k)||0)+1)});
      let bestKey="";
      let bestCount=-1;
      counts.forEach((count,k)=>{if(count>bestCount){bestCount=count;bestKey=k}});
      const majorityShare=bestCount/dates.length;
      if(majorityShare>=0.9)return dates.find(date=>dateKey(date)===bestKey)||dates[0];
      return new Date(Math.min(...dates.map(d=>d.getTime())));
    }
    function saleValidity(products){
      const today=new Date();today.setHours(0,0,0,0);
      const fromDates=products.map(p=>parseDateValue(first(p,["discount_valid_from","valid_from","sale_valid_from"]))).filter(Boolean);
      const from=fromDates.length?new Date(Math.min(...fromDates.map(d=>d.getTime()))):today;
      const until=mainUntilDate(products);
      return until?`Sale valid ${prettyDate(from)} - ${prettyDate(until)}`:`Sale valid from ${prettyDate(from)}`;
    }
    function normalTitle(row){return getTitle(row).trim().toLowerCase()}
    function groupProducts(rows){const map=new Map();rows.forEach(row=>{const title=getTitle(row)||`Product ${getId(row)}`;const groupKey=normalTitle(row)||getId(row);if(!map.has(groupKey))map.set(groupKey,{title,brand:getBrand(row),category:getCategory(row),imageRow:row,items:[]});map.get(groupKey).items.push(row)});return [...map.values()]}
    function categoryDescription(category){const c=String(category||"").toLowerCase();if(c.includes("collagen"))return"Marine collagen options for retail, wholesale and formulation needs.";if(c.includes("gelatine")||c.includes("gelatin"))return"Fish gelatine supply for food, capsule, cosmetic and specialist applications.";if(c.includes("peptide"))return"Peptides and tripeptides for wellness and private-label product development.";if(c.includes("moss"))return"Sea moss and wellness ingredients for modern supplement ranges.";if(c.includes("private")||c.includes("label"))return"Private-label support for brands that need reliable supply and guidance.";return"Selected Sir Francis products and supply options for serious buyers."}
    function groupedByCategory(groups){
  const preferred = [
    "marine collagen",
    "fish gelatine",
    "peptides",
    "sea moss",
    "baking",
    "snacks",
    "more specials"
  ];

  const map = new Map();

  groups.forEach(group=>{
    const category = group.category || "More Specials";
    if(!map.has(category)) map.set(category, []);
    map.get(category).push(group);
  });

  function rank(category){
    const c = String(category || "").trim().toLowerCase();

    if(c.includes("health")) return 9999;

    const index = preferred.indexOf(c);
    if(index !== -1) return index;

    return 500;
  }

  return [...map.entries()].sort((a,b)=>{
    const diff = rank(a[0]) - rank(b[0]);
    if(diff !== 0) return diff;
    return a[0].localeCompare(b[0]);
  });
}
    function categorySectionHTML(category,groups){return`<div class="category-section"><div class="category-banner"><div class="category-name">${escapeHTML(category)}</div><div class="category-desc">${escapeHTML(categoryDescription(category))}</div></div><div class="row row-cols-3 g-3">${groups.map(groupCard).join("")}</div></div>`}
    function offerRow(row,priceOverride,mainUntil){const deal=priceOverride||getDealPrice(row);const original=getOriginalPrice(row);const size=getSize(row);const save=saveText(row,deal);const originalNumber=numberFrom(original);const dealNumber=numberFrom(deal);const showWas=original!==""&&Number.isFinite(originalNumber)&&Number.isFinite(dealNumber)&&originalNumber>dealNumber;const note=productDateNote(row,mainUntil);return`<div class="offer-row"><div class="d-flex justify-content-between gap-2 align-items-end"><div><div class="sale-price">${escapeHTML(money(deal))}</div>${size?`<div class="sale-size">/ ${escapeHTML(size)}</div>`:""}</div><div class="text-end">${showWas?`<div class="was-price">Was ${escapeHTML(money(original))}</div>`:""}${save?`<div class="save-text">${escapeHTML(save)}</div>`:""}</div></div>${note?`<div class="product-meta mt-1" style="color:var(--cb-purple);font-weight:900">${escapeHTML(note)}</div>`:""}</div>`}
    function productDateNote(row,mainUntil){const until=parseDateValue(first(row,["discount_valid_until","valid_until","sale_valid_until"]));if(!until||!mainUntil||dateKey(until)===dateKey(mainUntil))return"";return`This sale ends on ${prettyDate(until)}`}
    function groupCard(group){const meta=[group.brand].filter(Boolean).join(" · ");return`<div class="col"><article class="card product-card"><div>${imageHTML(group.imageRow,group.title)}</div><div class="card-body p-3"><div class="product-title">${escapeHTML(group.title)}</div>${meta?`<div class="product-meta mt-1">${escapeHTML(meta)}</div>`:""}<div class="mt-2">${group.items.map(row=>offerRow(row,null,group.mainUntil)).join("")}</div></div></article></div>`}
    function clearancePrice(row){return first(row,["price","clearance_price","discounted_price","sale_price","special_price"])}
    function brochureFilename(validity){
  const match = validity.match(/(\d{1,2}\s+\w+\s+\d{4})\s*-\s*(\d{1,2}\s+\w+\s+\d{4})/i);

  if(match){
    return `Sir Francis Sales Brochure ${match[1]} - ${match[2]}`;
  }

  return "Sir Francis Sales Brochure";
}
function downloadPDF(){

  const originalTitle = document.title;

  const validity =
    $("validityTop").textContent || "";

  document.title =
    brochureFilename(validity);

  window.print();

  setTimeout(()=>{
    document.title = originalTitle;
  },1000);
}
    async function init(){try{const products=await loadProducts();const clearanceRows=await loadClearance();const byId=new Map(products.map(p=>[getId(p),p]).filter(item=>item[0]));const saleRows=products.filter(row=>getId(row)&&hasDiscount(row));const validity=saleValidity(saleRows);$("validityTop").textContent=validity;$("validityBottom").textContent=validity;const mainUntil=mainUntilDate(saleRows);const groups=groupProducts(saleRows).map(group=>({...group,mainUntil}));const clearanceItems=clearanceRows.map(row=>{const id=getId(row);const product=byId.get(id)||row;return{product,price:clearancePrice(row),id}}).filter(item=>item.id&&getId(item.product));const categoryGroups=groupedByCategory(groups);$("specials").innerHTML=categoryGroups.length?categoryGroups.map(([category,items])=>categorySectionHTML(category,items)).join(""):`<div class="empty">No discounted items found.</div>`;$("clearance").innerHTML=clearanceItems.length?clearanceItems.map(item=>`<div class="col"><article class="card product-card"><div>${imageHTML(item.product,getTitle(item.product))}</div><div class="card-body p-3"><div class="product-title">${escapeHTML(getTitle(item.product)||`Product ${item.id}`)}</div><div class="mt-2">${offerRow(item.product,item.price,null)}</div></div></article></div>`).join(""):`<div class="col-12"><div class="empty">No clearance items found.</div></div>`;$("status").textContent=`Loaded ${groups.length} grouped specials and ${clearanceItems.length} clearance items.`}catch(error){console.error(error);$("status").textContent="Could not load live sheets.";$("specials").innerHTML=`<div class="col-12"><div class="error">Could not load live sheets. Check the published TSV links.</div></div>`}}
    init();
  </script>
</body>
</html>
