=== Noti - Activity Notification ===
Contributors: vasyltech
Tags: user activity, audit log, notifications, tracking
Requires at least: 4.7.0
Requires PHP: 7.0.0
Tested up to: 5.9.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Totally free, infinitely configurable, and powerful website activity monitoring and alerting plugin for WordPress projects of any scale.

== Description ==

> Noti - Activity Notification (aka Noti) plugin is your single-stop shop for all you need to track any WordPress website activities. And it is completely free.


A FEW QUICK FACTS

* Noti is completely free of any charges. All the code that runs on your server(s) will always be free. There are no hidden fees, PRO versions, paid add-ons, extensions, etc.
* Noti does not capture or send externally any information about your website or how the plugin is used.
* Noti does not include advertisements of any kind (no banners, cross-sales pitches, or affiliate links).
* Noti functionality is based on the WordPress core concept of actions and filters. So, inherently, it integrates with ALL WordPress plugins, themes, and WordPress core itself.
* You can create an infinite number of event types to track or use any existing event types from [the public Github repository](https://github.com/vasyltech/noti-event-types) that is continuously growing.
* It works well on both single and multi-site WordPress websites.
* Noti comes with a powerful and flexible conditions library so you can define under which condition(s) to track desired activities.
* The initial plugin's version already includes three different ways to send alerts (via WordPress embedded email function, webhooks and dump logs into a file). More free notification types will be available as the plugin evolves.
* Noti is optimized for large-scale websites and comes with the ability to aggregate similar events over a defined period of time. This potentially can reduce DB storage usage by 50% or more.


NEED A NEW FEATURE? JUST ASK!

Noti is a brand new plugin, so naturally many useful, user-friendly and polished features may be missing. My initial intention was on preparing a solid and healthy foundation. From here it can grow and grow fast.

Pick any new feature or enhancement that you like/need and I will gladly add it in future releases. It literally can be any free or paid feature that is available in other "user monitoring and alerting" plugins and if it requires only my time, I will prioritize it. That is why please [subscribe to the regular email notifications](https://mailchi.mp/3a13b922c0bd/getnoti) where I will be disclosing new feature releases and announcements.

My only ask for you is to help spread awareness about Noti. More active installations - faster new features will be added.


HOW DOES IT WORK?

Noti is based on the WordPress core concept of hooks (actions and filters). When a hook is triggered, it typically carries enough information about the event. That is why 9 out of 10 times it is just a matter of “listening” for certain hooks and storing carried information in DB. Of course, sometimes, you have to take into account certain conditions, enrich information by calling some other function, or even combine data from multiple hooks. The good thing is that Noti allows you to do all this without writing a single line of code.

To be able to “listen” for any hook, you create a new event type and specify with just a few lines of JSON-based configurations the hook you want to listen to, information that you want to capture, and, if needed, conditions under which event should be captured. Configurations may look intimidating at first, so please do not hesitate to reach out to me and I will guide you through the process and help you to define the desired event type.

When the defined event type becomes active, Noti will listen and persist every occurrence of that event in the dedicated database tables in the most efficient manner.

As a bonus feature, you have the ability to subscribe to any specific event type and receive email notifications, configure to send these events to external API (webhooks), or log these events in a separate file. As the plugin evolves and grows in popularity, I’ll be adding more free types of notifications like SendGrid, Mailchimp, Push Notifications, Slack, etc.


WHY IS IT FREE? WHERE IS A CATCH?

Seriously. No catch. No hidden agenda. I wanted to build this product for years and finally, while experiencing quite a bumpy time in my personal life, I found a remedy in building Noti.

I'm a financially independent principal engineer leading a handful of strategic digital products for the biggest digital media company in the Western hemisphere. On another hand, I also maintain one of the most popular user access management plugins, [Advanced Access Manager](https://wordpress.org/plugins/advanced-access-manager/) which generates great passive income for me. In short, money is not a priority anymore, so it was time for me to start giving something back.


HOW DOES SUPPORT WORK?

From the extensive experience of managing other digital products (including a few WordPress plugins), I recognize that support does not scale well with just one person in charge. However, I will do my best to answer any questions you may have on the official WordPress forum or on [Github](https://github.com/vasyltech/noti-activity-notification).

Also, you are welcome to contribute to the product with your code, transactions, new event types or help me answer any questions that other folks may have. I’m hoping that over time we will build a strong community around this product and evolve it beyond imagination.

Please also [subscribe to the regular email notifications](https://mailchi.mp/3a13b922c0bd/getnoti) where I will be disclosing new feature releases and announcements.


== Installation ==

1. Upload `noti-activity-notification` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Event Log with the list of captured events
2. Event type list with the ability to create new types
3. Manage existing event types or create new
4. Group event types into categories
5. Configure basic settings, notification types, and email template

== Changelog ==

= 0.1.0 =
* Added: Upgrade plugin mechanism [https://github.com/vasyltech/noti-activity-notification/issues/2](https://github.com/vasyltech/noti-activity-notification/issues/2)
* Added: Term Created event type
* Added: Term Updated event type
* Added: Term Deleted event type

= 0.0.2 =
* Fixed Bug: fread(): read of 8192 bytes failed ... [https://github.com/vasyltech/noti-activity-notification/issues/1](https://github.com/vasyltech/noti-activity-notification/issues/1)

= 0.0.1 =
* Initial and minimalist version