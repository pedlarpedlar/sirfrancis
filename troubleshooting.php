Troubleshooting:

<!-- CLEANUP -->
delete tables but ensure they are also not included in any sql queries (remove them):
page_views, login_attempts, 

clean up - delete unneeded pages from online and localhost


<!-- MARKETING AND BRAND -->
products to add: full range from fox n swan, bakeaton and manolis for now. And affiliate products from all our brands

work on high cost branding and marketing bulk/gifts/high profit products (6k per month budget) and see if sales increase.

Check downloads folder for image ideas

Check downloads folder for extra files to add to website. employee handbook impressive also.

Update images for products 1-50 or 1-90, low quality was used.

Eventually i'm going to have to make a thumbnail version of the product images and only the product page should have the actual large images. product page takes forever to load. to do this, create a folder inside the same product image folder called "large-images" and place all the existing images there, then replace all the current images with small versions. but this needs to be done dynamically in a way where, uploading the image to website automatically reduces and compresses it, or you have to manually add "thumbnails" and "large images" with the same name, or something. this is looking complicated.

Using canva, design some images for the return policy page, just with the headers on the images, so that the page looks like a well furnished home.

Add the "material" option to the bulk uploader and export. This can advise clients what material the actual item is, or posssibly what type of pacaking/box it comes in, e.g. for gifting, I could say "Bamboo Wooden Tray" and "Kraft Box with PVC lid". VERY IMPORTANT: Look at pictures on the net for "dried fruit trays" and copy that for adding to range. and the hepsiburada trays. Get expensive and/or branded ribbons for gifting (purple and gold) and special cards printed for them. Folded cards (for notes) and non folded..

<!-- MARKETING AND BRAND END -->





<!-- ANALYTICS START -->

Manage users table (where you also view new user registations) should have all info about client and preferably where they found our lead. also, their IP address, location, and product viewing activity (clicks, searches, cart/wishlist/compare, time spent per session, abandoned cart time, etc) should be available for statistics.

Create an analytics page

show the actions neatly and orderly on analytics page.

Analytics page can also include: most sought item (wishlist), most abandoned cart items, most searched term, most bought item, most bought size, most viewed item, etc

log all actions, registration/order/checkout/apply and remove coupon/login/logout/calculate shipping on cart and checkout/ship to different address checkbox on checkout and register user checkbox/ pricelist download / change password on profile / change address on profile / search terms / logins and login attempts / logout / password resets and forgot links / --------- click on reviews description product details on product page / submit review / clicking on pagination buttons on reviews from product page and products page/write review modal/click on read reviews link / all of the filter items on product page  

Create an admin dashboard with sales, leads, etc like syncitt's.

Try to make it connectable to syncitt.
<!-- ANALYTICS END -->





<!-- ADD FEATURES AND PAGES -->
if a user places an order without registering account and doesn't get to complete payment, the only link to the order is in the actual email and there's no way to pay via payfast again. so make that email "pay now" button go to a "paylink" where all of the order details are captured with a token, allowing user to be redirected immediately to payfast.

basically, mimic the checkout.inc.php page with the order details and redirect them to checkout. And if the paynow method is "eft" then the user should be redirected to the bankingdetails page on candybird, do this for all paynow buttons (emails, order_details, profile page, etc).
(halfway done! just need to complete the "view carts" page so that i can actually checkout as admin) BRAINSTORM: to make it easier for user (and me), add a add-to-cart button next to each size in the pricelist. use a different link like shop-pricelist so that it doesn't tamper with the printable pricelist. 

(^continuation) if i as the admin add to cart then it should generate a syncitt invoice directly. basically, on that page (restricted by admin login), the products should come from the candybird database and get inserted into the syncitt database and then instead of checking out, redirect to the invoice on syncitt where you can then add the customer details. If that product isn't already added to syncitt then add as a new product perhaps with a suffix or order note "online-store" to define the orders and to separate the items for filter purposes. Also make it such that a user can share their cart with me (admin) and if I view it from the admin panel then i can check-out that cart. I should be able to remove/add/update the items in the cart and empty/save cart. There can also be a list of "carts" which i can either delete or checkout. this way, user can do their own shopping "manually" from pricelist and then send to me, or checkout on their own, or simply use the website itself to shop and checkout.

users should be able to change their rating on a product

Adjust placeholder image when creating single product

To compare, add Material, Features, Properties etc.

make page manage_order.php: Add order details here. Admin should be able to: - Add/remove products - Update quantity - Add/remove coupon - Add/remove/edit shipping method - Add/remove/edit shipping charge - All user info should also be editable but only on the users page, not the orders page.


create a way for admin/staff users to add to cart and checkout on behalf of customers (guests or users). when it comes to payfast payment they must just checkout themselves from their profile, if it's a guest give them a user and details like email and 12345 (and inform them to change their password.)

also log these actions i.e. admin placed the order.

admin Manage order page. 


<!-- ADD FEATURES AND PAGES END -->



<!-- COUPONS START -->
On the manage coupons page, create a sort of "coupon alert" for subscribers. This will basically involve the flash sale announcements and any other related emails, so try to make it pretty customable. Almost like a rich text editor, with an email preview, and a selectable box of who all to send to (with the ability to add custom email addresses).

Work on the coupon-shipping thing on checkout page

Let users add a shipping discount coupon instead of the default being low shipping. Otherwise we won't benefit from the people who don't mind paying the full shipping price. This will also allow us to see how many people use coupons against those who don't.

on manage_coupons page i haven't yet been able to figure out why the category_ids and product_ids don't get added to database, only first number in list does. Also, ensure that selecting the "categories" and "specific items" options open the respective input form field so that only one at a time can be filled.

on apply_coupon_function and apply_coupon pages, adjust the code so that the coupon iterates through the products instead of rejecting the entire cart when there's an error. For example, if I have a coupon available on Salted Nuts, and i add raw nuts AND salted nuts to my cart, let the user get the coupon available for the salted nuts instead of rejecting the entire cart because it's only valid on salted nuts.

Implement coupon on shipping.

//Adjust coupon apply pages to apply shipping coupon

Apply coupon to shipping on checkout inc page, so that it reflects the correct amounts in order details and emails.

checkout inc php , shipping coupon still not applied - troubleshoot this and check session variables etc.
<!-- END COUPONS -->



<!-- TROUBLESHOOTING -->

pricelist.php and pricelist-download, work on affiliate products, too many sizes so it's making table big. try to solve.

when inserting/updating rows via bulk uploader, ensure that discount_amount is inserted.

when updating rows via bulk uploader, ensure that previous images related to those product ids are updated/deleted.




Make an inventory. For 'in stock' badges and 'out of stock' and also an email notification system when inventory goes below x.

On checkout, they must check or uncheck the box "substitute my items with closest match if unavailable" or not. if unchecked, user will be refunded/credited the difference.

Make a cron job that executes the website admin's special requirements. For example, I would like to show a different random 5 products on sale (even 2%) just so that the homepage has got products on sale at any given time. This can be a weekly change. The cronjob can select any 5 prouducts.

make the admin/shipping page nicer

Reviews on product page says "read reviews (2)" but only one review is available.

Work on eft payment selection (don't redirect to payfast)

pretty-inize the admin category add page, currently a bit raw















//pay now buttons still on profile page? redirect to order details page for now

//log when pricelist gets downloaded

//on profile page, check url for #sign and open tab based on that..

//toggle for username and email on checkout page doesn't work since i edited it..

// checkout total not correct with discounts and coupon, cart total is, copy that.




//Make a pricelists downloadable, with each size and category as headers/dividers.

//TEST WEBSITE - desktop and mobile, guest user and logged in user. place orders. add to cart from every page. 

//TEST order checkout page: with and without create user.



















COMPLETED/PUT ASIDE FOR LATER:



//make a Banking Details page

//add banking details add to sitemaps



//add global-services, wholesale, return policy and other important pages from menu to sitemaps

// make a very simple action logger to log all actions (php and jquery)

// get users geolocation and city/address and add to table

// make a tracking table for all unique ip addresses which aren't bots.

// Adding to cart from the quick-view on the products page: the price feild has the price x4, strange. Updated add-to-cart.php find old backup in add-to-cart-quickview.php

// Very important: Coupons; Checking out, payfast etc grabs the order total (when there are SALE items) with the coupon subtracted from the subtotal, not the discounted subtotal, so they end up paying higher discount. Also, make it such that the coupon counts and excludes the items which have a discount_rate > 0 so that the discount is only applied on the full priced items. Also include a note on the cart/checkout/coupon adverts that the coupon is valid on non sale items only. Pages involved: cart, checkout, checkout.inc.php, off canvas cart, off canvas mobile cart. 


//when adding, updating and removing from cart, the apply-coupon page should also run, since the validation and product_ids should be updated in there as well.

// On checkout page, auto select the province and country from select boxes and calculate trigger shipping.

// remove coupon from checkout and cart if not in session


// Make orders increment by a random number between 10 and 100 each time a person checks out. this will avoid people being unconfident or untrusting in our services

// on manage coupons page, give admin lots of options. Legible on discounted products. legible on certain products (give product ids). legible sitewide or not. legible for particular users or not. expiry date. number of usages in total (across all users). number of usages per user.

// Create a "manage coupons" page for admin. Coupons can be for order total excl. shipping (before vat), and shipping (separately). Coupons are only applied to items not already marked down (e.g. if it's a 15% coupon, you get 15% off cart items that are not discounted.) Allow user to change this: Coupon valid on ALL items, or coupon valid on full-priced items only. Update:  Done, alhamduillah, in half a day's work.

// if a user applies a coupon, then adds more products to cart, it should auto update. also, it should only apply on items which do not already have a coupon. not sure how to handle this. perhaps whenever the user enters the cart or checkout page it should 'refresh' the coupon calculation. Basically, sometimes it adds the coupon. It's a bit messy. Use a separate system and don't add coupon the way it's currently being added. If added/updated from cart, it adds the coupon, but not from a separate page. see and figure out difference. Also, coupon should always show and user should be able to re-add the coupon and not get double coupons added. uPdate: Done, alhamduillah, in a day's work.

// Create an error-div on the checkout page so that users may see any errors.

// On index page, have a image with all our packaging instead of graphics.

// index page should be more dynamic. make a page called "slides" and put all array data there and then just foreach loop it into the index page. will make things much easier for new websites

// On index page, banners should be more about the website instead of just images. doesn't have to have buttons, just make it informative.

// make manage_orders table responsive, even just a scroll bar should work. (update: it is actually responsive, clicking the ID opens the hidden fields. not yet sure how to make a button tho.)

// readjust return policy page, padding margins etc quite squashed

// create page: global-services

// create page: wholesale
// Sometimes category products show, sometimes not. E.g. category 44 shows items but 42 doesn't. very strange. Solved: The searchFilter function needed adjusting

// Same with csv uploading. Skipped rows appear in the skipped csv file but those aren't the only skipped rows. figure out a way to work around the quotation marks and products not uploaded. Solved: Csv files must be proper in text only and in quotation marks (fields like description etc)

// We need to somehow merge the users sessions if they log in from 2 browsers or so.
// when checking out (and selecting "create user"), redirect payment the same way but just log them in and grab their user ID etc. Also do error checking like "this username already exists" etc without tampering with their cart items.

// a guest can checkout - without registering. and they can pay. A guest can checkout with registering but an error occurs and he's not redirected to payment page.

// change footer and footer logo color to a little less warm. also make the base colors slightly warmer, it's toooo cold now.

// Make a Remove from Compare list

// On emails, add the shipping amount to the savings column, and where it says shipping, include the initial amount they'd pay if they were to pay shipping. Also ensure that any other details such as coupon codes are shown on emails.

// Checkout not working on Mobile, check logs.

// Figure out a way to echo the filtered list view results as well. Data is already there, should be a quick thing.

// On product page, and quick-view, the full categories should show, not just the end-category. Important. And categories should be clickable.

// Change fonts in emails. The numbers are difficult to read.

// solve this in paynowform // $order_id = $user_id = $fetched_billing_first_name = $fetched_billing_last_name = $fetched_billing_email_address = $cartTotal = null; - the document has an error when guest checks out directly and the payment email doesn't get sent to them. however, it's essential to null them for certain pages, and esssential not to include them for other pages. find a middle path or simply make 2 separate documents for thiss purpose.
Send email to all existing candybird clients that the website has a new look and we have a coupon for existing clients.

// Sorter: When fetching products (fetch_all_produdcts.php) and sorting by relevance, price, a-z etc only works if the products are NOT grouped by product_group. It's either or. That's why it works when filtering in filter_products.php, because that file doesn't group by product_group and shows each size indivudually. So we can decide what to do here. Perhaps if there is a sorter in the url, then the applyFilters() function should load? Update: done.

// All emails where order items are displayed, should have an extra field to include the coupon and vat amounts before the total. Also for the order details page. 

// on order_details page, get product details from the order_items table NOT products table.

// When a guest user tries to check out (it works,) but they should be taken to the pay page instead of redirecting to login. Just take them to a non-session thank you page which tells them to check their email and that they should register as a user to get live updates about their order history.

// Send email when user subscribes.

// Send admin email when user subscribes and unsubscribes.

// If user is not logged in, they can place an order but they should be redirected to payment portal immediately. 

// On profile orders page, make the pay buttons work.


// Set up cronjobs to execute daily for: generate_google_shopping_cart_items.php and generate_sitemap.php. Let it run at 2:00AM daily.

// Create a daily backup that occurs at 00:00 everyday, full database should get backed up. (setup with cronjob)

// Only problem still experiencing is the related items not opening quickview. The rest is sorted: it adds to cart, wishlist and compare. Once everything is completed, uncomment the tracking analytics (uncommend js from footer and uncomment php from session pages) UPDATE: sorted the quickview alhamdulillah. in footer, after updating data in modal, the initialization of slick should be done manually and you should not call the slick function prematurely.

// related products can't do any function (add to cart/wishlist, some errors and doesn't show prices) - i played around with this, the slick isn't the issue at all. There is a separate issue related to the quick view which i haven't been able to define. - Update: I just removed the quick-view for now.

// client Pay page. 

//Thank you page. 

// Cancel page.

// Replace payfast sandbox

// Ensure all images are 300px height so that it looks neat on website.

// Make products searchable on google

// To start using website: enable payfast, add banking details, and list stocks (bulk stocks, resellers stuff, etc). make thank you page, etc.  SPECIALISE IN GIFTING, have lots of gifting options.

// Order details page: Not showing order items.

// Payment Email Fail email: not showing order items and deliver to and payment method.

// Signature-checks are failing when making payment, not sure why this is happening.

// Move website to live (candybird.co.za) out of newsite. Keep newsite available for testing and maintenance purposes so that edits are not done on the live page.

// create a forgot password page.

// create a reset password page.

// make the login page error below the form, not in a notification.

// Payfast live works except that the signature validation fails. This means client can't get a successful payment message. 

// Payfast live works except AND signature validation is successful. Client now gets a "paid" status. However, email isn't sending. Check closely why this is happening. Redo email placeholders, send a basic hardcoded email, etc.

// redirect after login doesn't work for admin pages. not sure about client pages.

// redirect after login doesn't work for client pages

// Big issue: quick-view and add to cart is not adding. check pages that i edited and see what's going wrong. -- update: no idea what was wrong, it's back to normal. i cleared cache and commented the analytics tracking 

// On wishlist and cart pages, if empty, where the "oh no" part is, add a few product suggestions. Kind of like related products. After making related products work.

// Work on "Search" as soon as the product page is perfected.

// Add "properties" to import and export files. Advise user that this should strictly be a comma separated list of product features/properties for user to search, such as "Omega Rich", "Weight Loss", "High in Amino Acids", etc.

// Clicking on any Category should take users to a page (like products page) but filtered with that category, and a left side panel with the selected category. There is a layout like this existing on one of the grid pages, look inside the canydbird folder. Needs time: Ensure that categories filter properly and pagination should work seamlessly with it

// pagination on results/products page a bit off.

// Create a page-tracker

// Create a session login and logout tracker 

// Create a 404 page and redirect it

// Create an Email Scheduler page.

// make pages: iqaala.php, about.php.

// Payment emails not being sent

// Mark order as complete after order is shipped out (when they get the emails, basically.)...

//clean up UI, make bg outline much lighter, make fonts bolder, get the checkout and payfast to work, make featured items, remove the unwanted menu items for now like reseller/pricelist etc, add images and start advertising asap.


// Add image url upload to csv file (when exporting and importing)

// Make a note on emails that goods will be shipped to nearest locker.

// Send tracking information email to user, create steps on backend for admin to control status etc. 

