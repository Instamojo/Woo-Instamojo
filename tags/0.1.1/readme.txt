=== WooCommerce Instamojo ===
Contributors: instamojo
Donate link: https://www.instamojo.com
Tags: commerce, e-commerce, ecommerce, online store, sell digital downloads, sell online, shop, store, wordpress ecommerce, WordPress shopping cart, sell event tickets, sell subscriptions, sell memberships, sell physical goods, payments, easy payments, payments button, widget
Requires at least: 3.3
Tested up to: 4.6.1
Stable tag: 0.1.1
License: MIT
License URI: http://opensource.org/licenses/MIT

Sell & collect payments instantly for almost anything -- directly from your WordPress website.

== Description ==

We're on a mission to make mobile/desktop payments simple, accessible & fun -- "Think Payments, Think Instamojo".

We help individuals & small businesses collect payments instantly through a unique short-link (e.g. - imojo.in/demo) which has powerful capabilities of analytics, CRM, payment processing, security, 100% seller-buyer protection, Appstore etc to help grow businesses on mobile/desktop.

We are being used daily like a life-line by thousands including artists, freelancers, offline stores, facebook sellers, event organizers, artisans, authors, SOHO business owners, infopreneurs, tutors & many many more.

*Features & Benefits:*

- No paperwork or IT knowledge required. Even owning a website is optional. 
- 30-seconds on-boarding process to get started without any extra cost. 
- Beautiful & intuitive UI/UX to give you industry's best sales conversion.

So get started and start selling ebooks, reports, music, software, templates, event tickets, photos, tutorials, subscriptions & more like thousands others who are already making millions.

== Installation ==

This section describes how to install the plugin and get it working.

1. Make sure you've installed the [WooCommerce plugin](https://wordpress.org/plugins/woocommerce/) and it is activated. Note that you cannot install WooCommerce - Instamojo if you didn't install WooCommerce.
2. Search for "WooCommerce - Instamojo" on the WordPress Plugin directory or [download it](http://downloads.wordpress.org/plugin/woo-instamojo.zip).
3. Install the plugin.
4. Activate the plugin through the 'Plugins' menu in WordPress.

Once the plugin is installed and activated, you will be able to access a new menu under settings called Instamojo.

= How to use the plugin? =

1. Now go to WooCommerce's settings tab (left sidebar on your WordPress dashboard) --> Settings --> Checkout --> Instamojo.
2. Check "Enable Instamojo Payment".
3. Now go to WooCommerce's General settings tab.
4. In WooCommerce's General settings tab make sure the currency is set to "Indian Rupee (₹)".
5. Now go to your [Instamojo Dashboard](https://www.instamojo.com/dashboard).
5. Create a product by clicking on **Add a Product** on your Instamojo dashboard and choose the category **Other**.
6. Set the price to Rs. 10 and enable **"Pay what you want"**. Under **Title** and **Description**, you may enter something that describes your business and the nature of the products being sold.
7. Under **Advanced settings** of the same product there's a field **Custom Redirection URL**. Here if your website's url is **http://www.example.com** then use **http://www.example.com/?wc-api=wc_instamojo** as **Custom Redirection URL**.
8. Click on **Add Product to Store** to save the product.
9. Copy the product URL and paste this in **Instamojo Payment Link** field of the Instamojo settings page in WooCommerce. URL's format is usually: **https://www.instamojo.com/username/slug/**.
10. On the product page go to **More options** and click on **Custom Fields**. Create a custom field called **Order ID** and mark it as **required**. Click on **Add Custom Field** to save this custom field. 
11. After the custom field has been created **Existing Custom Fields** section will appear under it. Copy the name shown under **Field ID** column, its format is **Field_xxxx**, where **xxxx** are some numbers(Note that this is case sensitive!).
12. Enter this name in the **"Custom field"** field of the Instamojo settings page in WooCommerce.
13. If you prefer, you can create a Wordpress page with some custom thank you information to be shown to your customer after a successful payment. If you create such a page, you can enter the URL to this page in **"Thank You Url"** field Instamojo settings page in WooCommerce. **Note**: If you do not create such a page, then make sure this field is empty.
14. The **"Thank You Message"** will be passed with your **"Thank you URL"** as a GET parameter named **"msg"** along with another parameter named "class". So, if your **"Thank You URL"** is say **"http://www.example.com/thank-you.php"** and **"Thank You Message"** is **"Payment received"** then we will call this page as **http://www.example.com/thank-you.php/?msg=Payment+received&class=woocommerce-message**.
    The value of **class** here will be either **woocommerce-message** or **woocommerce-error** depending on whether payment was sucessful or not.
15. Save the Instamojo settings page in WooCommerce.


== Changelog ==

= 0.1.1 =

* Fixed the error message related to session_start().
* Updated supported WordPress version to 4.6.1.

= 0.1.0 =

* Fixed the issue with cart clearing on successful payment.
* Updated supported WordPress version to 4.6.
* Updated field title to use "Product Link" instead of "Payment Link".

= 0.0.9 =

* Updated README to use the term Product instead of Payment link.
* Updated supported WordPress version to 4.5.3.

= 0.0.8 =

* Now removing spaces from the full name to fix the signature error some users are getting.
* Updated supported WordPress version to 4.5.2.

= 0.0.7 =

* Fixed XSS vulnerability in checkout/order-received/ page. Discovered by Ajin Abraham(https://opsecx.com/).
* Updated supported WordPress version to 4.4.2.

= 0.0.6 =

* Added support for failed payments.
* Updated supported WordPress version to 4.3.
* Small update to the Installation docs.

= 0.0.5 =

* Handle phone numbers with "+" properly.
* Added '/' at the end of the payment detail URL to avoid an extra redirection.

= 0.0.4 =
* Fixed $_SESSION issue faced by users with non-persistent $_SESSION.

= 0.0.3 =
* Fixed sorting issue for older PHP versions.
* Added an option to provide custom "Thank You Message" to user in case of successful payment.
* Plugin is much more smaller now.

= 0.0.2 =
* Various bug fixes related to name, email length.
* Some changes related to backward compatibility.

= 0.0.1 =
* Initial release.