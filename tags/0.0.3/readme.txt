=== WooCommerce Instamojo ===
Contributors: instamojo
Donate link: https://www.instamojo.com
Tags: commerce, e-commerce, ecommerce, online store, sell digital downloads, sell online, shop, store, wordpress ecommerce, WordPress shopping cart, sell event tickets, sell subscriptions, sell memberships, sell physical goods, payments, easy payments, payments button, widget
Requires at least: 3.3
Tested up to: 4.1.1
Stable tag: 0.0.3
License: MIT
License URI: http://opensource.org/licenses/MIT

Sell & collect payments instantly for almost anything -- directly from your WordPress website.

== Description ==

We're on a mission to make mobile/desktop payments simple, accessible & fun— "Think Payments, Think Instamojo".

We help individuals & small businesses collect payments instantly through a unique short-link (e.g. - imojo.in/demo) which has powerful capabilities of analytics, CRM, payment processing, security, 100% seller-buyer protection, Appstore etc to help grow businesses on mobile/desktop.

We are being used daily like a life-line by thousands including artists, freelancers, offline stores, facebook sellers, event organizers, artisans, authors, SOHO business owners, infopreneurs, tutors & many many more.

*Features & Benefits:*

- No paperwork or IT knowledge required. Even owning a website is optional. 
- 30-seconds on-boarding process to get started without any extra cost. 
- Beautiful & intuitive UI/UX to give you industry's best sales conversion.

So get started and start selling ebooks, reports, music, software, templates, event tickets, photos, tutorials, subscriptions & more like thousands others who are already making millions.

== Installation ==

This section describes how to install the plugin and get it working.

1. Search for "WooCommerce - Instamojo" on the WordPress Plugin directory or [download it](http://downloads.wordpress.org/plugin/woo-instamojo.zip).
2. Install the plugin.
2. Activate the plugin through the 'Plugins' menu in WordPress.

Once the plugin is installed and activated, you will be able to access a new menu under settings called Instamojo.

= How to use the plugin? =`

1. Make sure you've installed the WooCommerece plugin and it is activated.
2. Click on settings under the plugin and first of all check "Enable Instamojo Payment". Make sure the Currency is set to "Indian Rupee(Rs.)" under WooCommerce's General settings tab.
3. Create a Payment Link on Instamojo under the "Services/Membership" option. Set the price to Rs. 10 and enable "Pay what you want". Once you create this link, copy the URL and paste it in the "Instamojo Payment Link" field of the Instamojo settings page in WooCommerce. Under "Title" and "Description", you may enter something that describes your business and the nature of the products being sold.         
4. Visit https://www.instamojo.com/developers/ (Make sure you are logged into your Instamojo account). You will find your API credentials here. Use these credentials to fill in the fields "Private Api Key", "Private auth token" and "Private salt" in the Instamojo settings page in WooCommerce.
5. On your payment link, click on "More Options" at the top of the page and then click on "Custom Fields". Now create a custom field called "Order ID" and mark it as "required". In the custom field creation page, hover over the field you just created. You'll see a field with the format "Field_<number>". Note down the full name (including the "Field_" bit. Note that this is case sensitive!). Enter this name in the "Custom field" field of the Instamojo settings page in WooCommerce. 
6. If you prefer, you can create a Wordpress page with some custom thank you information to be shown to your customer after a successful payment. If you create such a page, you can enter the URL to this page in the Instamojo settings page in WooCommerce. If you do not create such a page, the customer will be redirected to your homepage after a successful payment.
7. Edit the Instamojo link you just created and click on "Advanced settings". Save the link.
8. Save the Instamojo settings page in WooCommerce.
9. If you haven't already, add some products in WooCommerce.

  If your website's URL is "http://www.example.com/", you need to paste the following into the **"Custom redirection URL"**:

  **"http://www.example.com/?wc-api=wc_instamojo"**

10. The **"Thank You Message"** will be passed with your **"Thank you URL"** as a GET parameter named **"msg"** along with another parameter named "class". So, if your **"Thank You URL"** is say **"http://www.example.com/thank-you.php"** and **"Thank You Message"** is **"Payment received"** then we
will call this page as **http://www.example.com/thank-you.php/?msg=Payment+received&class=woocommerce-message**.

  The value of **class** here will be either **woocommerce-message** or **woocommerce-error** depending on whether payment was sucessful or not.

== Changelog ==

= 0.0.3 =
* Fixed sorting issue for older PHP versions.
* Added an option to provide custom "Thank You Message" to user in case of successful payment.
* Plugin is much more smaller now.

= 0.0.2 =
* Various bug fixes related to name, email length.
* Some changes related to backward compatibility.

= 0.0.1 =
* Initial release.
