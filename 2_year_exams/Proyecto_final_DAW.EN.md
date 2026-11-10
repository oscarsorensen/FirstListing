# Proyecto final DAM / DAW

## Guide for writing the report

This guide is meant to help you write and organize the final project report. The structure has been reviewed so that the work clearly addresses the learning outcomes and assessment criteria of the project module established in the official regulations.

The report must not be limited to describing the program you have developed. It must demonstrate that you are capable of analyzing a real need, designing a viable solution, planning its execution and establishing mechanisms to monitor and evaluate the project.

The explanations that appear under each section are guidelines to help you understand **what you are expected to develop**. You must always adapt them to your specific project.

------------------------------------------------------------------------

# 1. Introduction

In the introduction you must briefly present the project and allow someone who does not know it to understand what you are going to develop, what problem it aims to solve and what the general scope of the work will be.

Try to answer, in summary form, questions such as:

- What does the project consist of?
- What need or problem does it aim to solve?
- What users, clients or entities is it aimed at?
- What type of solution are you going to develop?
- What are its main functionalities?
- What technologies or main resources are you going to use?
- How will the rest of the report be organized?

The introduction should work as a general overview of the document. There is no need to go into technical detail yet; those aspects will be developed in later sections.

As a guideline, a length of approximately one page is usually sufficient.

-- PUT THE INTRODUCTION HERE

**ANSWER**

FirstListing is a web application that identifies which real estate listing came first. The user pastes the URL of a property advertisement, the system reads that page, extracts the key data — price, surface area, rooms, bathrooms, property type, reference number and description — and then searches its own database for the same property published elsewhere, grouping every matching advertisement into a single result ordered by how likely it is to be the same property.

The problem comes directly from my own work in a real estate agency on the Costa Blanca. The same property is routinely republished across several portals and agency websites with the same photos and almost the same information, but with small differences in the price, the description or the contact person. This makes the market confusing and wastes a lot of time, because it is hard to tell which version is the original and who actually manages the property. It does not only affect professionals: buyers can end up negotiating with an intermediary who simply copied someone else's advertisement.

The application is aimed primarily at real estate professionals, so it is a B2B product. The specific target is agencies on Costa Blanca South, where there are roughly 850–900 active agencies, and within those agencies the most relevant users are the back-office staff who create and maintain the listings, since they are the people who deal with duplicates every day. Private users could also use it, but they are not the commercial focus.

The solution is a client–server web application that runs in a browser and requires no installation. Its main functionalities are: user registration and login with three roles (agent, private, admin); submission of a listing URL; automated crawling of that page; AI-based extraction of the structured fields; an SQL scoring pass that compares the new listing against everything already stored; an AI comparison of the descriptions for the strongest candidates; a ranked results table; an administration panel with full CRUD, a raw-data viewer and a live crawler log; a bilingual EN/ES interface; an AI chat widget that explains the project; and the legal pages required for GDPR and LSSI compliance.

Technically the project combines Python 3 for the crawler, PHP for the web application and the AI scripts, MySQL for all persistent storage, and HTML5, CSS3 and vanilla JavaScript on the client side with no frameworks. The only external paid service is the OpenAI API, using the GPT-4.1-mini model for both field extraction and description comparison. Development was done on a local Apache and MySQL stack installed through Homebrew on macOS, with Git for version control and the repository hosted on GitHub.

The rest of this report is organised as follows. Section 2 analyses the productive sector, the companies in it and the needs that justify the project. Section 3 covers the design of the solution and its technical feasibility. Section 4 sets out the execution plan, resources, risks and economic assessment. Section 5 defines how the project was monitored and controlled. Section 6 presents the conclusions and Section 7 the sources consulted.

------------------------------------------------------------------------

# 2. Identification of needs in the productive sector

The first learning outcome consists of **identifying needs of the productive sector and relating them to projects that can satisfy them**.

In this first part you must demonstrate that your project does not appear in isolation: there is a professional context, companies, users and needs that justify the solution you propose.

-- PUT THE ANSWER for 2. HERE

**ANSWER**

FirstListing did not start as a technical exercise looking for a problem to solve. It started from something I ran into repeatedly while working in a real estate agency, which means the professional context, the companies and the users behind it are all real and identifiable rather than invented for the report. The sector is the Spanish online real estate market, and more specifically the agencies operating on the Costa Blanca, where I have direct experience.

The subsections below set out the companies that make up this sector, how a company of that type is organised, what the sector most often needs, what opportunities exist, what kind of project is required to respond to those needs, what characteristics that project must therefore have, and what legal and financial obligations would apply if it were built commercially.

## 2.1. Companies in the sector by their organizational characteristics and by the product or service they offer

Research companies that develop products or provide services related to your project.

It is not just about making a list of companies. You must observe what they do, what type of products or services they offer, which customers they target and what relevant characteristics they present.

The goal is to get to know the sector in which your project would be situated and to have real references against which to compare it.

You can study, for example:

- main activity;
- products and services;
- target audience;
- size or scope of operation;
- business model;
- technologies used, when relevant;
- characteristics that differentiate some companies from others.

-- PUT THE ANSWER for 2.1 HERE

**ANSWER**

Two different groups of companies matter to this project: the portals, which are the closest thing to a comparable product, and the agencies, which are the actual customers.

After researching the sector, drawing on both my own experience in it and a considerable amount of recent research, I have not found a company that does exactly what FirstListing does. The closest references are the portals Idealista and Fotocasa. Their main activity is operating online real estate listing portals, offering a search engine over property advertisements and the publication channel that agencies and private sellers use to reach the market. Their audience is double-sided, with consumers searching for property on one side and agencies paying to publish on the other. They operate nationally and earn their money through paid publication and subscription plans for agencies, supported by advertising.

What matters most for this project is what they do not do. Neither shows which advertisement was published first, and neither groups near-identical advertisements together so the original can be found. They make listings visible; they do not make duplication visible. That gap is exactly the space FirstListing occupies, and the fact that the two largest players in the sector have not closed it is the strongest evidence I have that the idea is genuinely new rather than simply unbuilt.

The second group is the agencies, which are the companies FirstListing would actually sell to, so they deserve as much attention as the portals. Their main activity is the sale and rental of residential property, and what they offer is a brokerage service rather than a product. On Costa Blanca South their customers are largely foreign buyers. They are small, usually a handful of employees per office, and operate locally, with approximately 850–900 active agencies in that area alone. Their business model is a commission on completed transactions. The most relevant characteristic for this project is how they publish: the same property reaches the market through several portals at once, through XML feeds, and through co-brokering agreements between agencies.

That last point is the important one. The distribution model these companies use is precisely what generates the duplicates, which means the potential customers and the cause of the problem are the same group of companies. That is unusual, and it is what makes the product a straightforward sell: they are not being asked to fix someone else's mess, they are being offered a way to see through their own.

FirstListing sits between the two groups. It is not a portal and not an agency, but a B2B software supplier selling a subscription tool to the second group, addressing a problem the first group has chosen not to solve.

## 2.2. Typical companies: organizational structure and functions of the departments

Choose one or several representative companies in the sector and explain how a company of that type could be organized.

You must identify its main departments or areas and explain what function each one performs.

In a software development company there could be, for example, areas of management, analysis, development, design, systems, quality, support, sales or administration. Not all companies will necessarily have the same structure.

The important thing is to relate the organization described to the reality of your project's sector.

-- PUT THE ANSWER for 2.2 HERE

**ANSWER**

The most useful company to describe here is FirstListing itself, because it is the type of company this project would create, and because it can be contrasted with how a larger software company in the same sector would be organised.

In its realistic form FirstListing is a micro-enterprise with a single founder, a small technology startup building a web-based system for analysing and clustering real estate listings. The founder acts as both CEO and technical lead, covering strategy, programming, database design, data processing and client communication. The structure is completely flat, which means decisions are fast and organisational complexity is close to zero.

Development is the core department from day one, because the company is fundamentally a software company. In the initial stage the founder also handles product design, basic marketing and basic accounting internally, while tax management and legal advice are outsourced from the beginning to guarantee compliance and reduce risk. That split is deliberate: the areas where a mistake is cheap are kept in-house, and the areas where a mistake is expensive are given to someone qualified.

If the company grew to a hypothetical 5 to 7 people, it would divide into functional areas: leadership (CEO / Product Owner), backend development, frontend development, data engineering, and infrastructure management. Leadership would set the direction, prioritise features and own the client relationship. Development would build and maintain the software, manage the database and keep the platform working correctly. Data engineering would own the crawler and parser, the components that acquire and normalise listing data. Infrastructure would take care of hosting, deployment, backups and monitoring. Marketing would study market needs, communicate the value of the product and attract clients, while administration would handle billing, payments and basic accounting. Tax and legal work would stay outsourced to ensure fiscal and legal compliance, and support, which handles client incidents, would become a separate role only once client numbers justify it.

During the first year the structure stays lean, with most advanced services subcontracted. Within five years, if the company grows, marketing and customer management could become internal roles, and development could split into separate backend and frontend positions. In ten years all key departments, meaning development, marketing and administration, could be fully internalised.

The principle underneath all of this is that the company size must match the scope and complexity of the product, not the other way round. A duplicate-detection tool sold to local agencies does not need a department structure on day one, and inventing one would be a cost rather than an advantage.

## 2.3. Needs most demanded from companies

Based on the previous research, identify which needs seem to be most frequently demanded by customers or users in the sector.

Think about real problems that can be solved through software: process automation, information management, communication, e-commerce, education, data analysis, mobility, security, entertainment, accessibility, etc.

It is not enough to state that a need exists. Whenever possible, justify why you consider it relevant based on your analysis of the sector.

**ANSWER**

The clearest and most frequently demanded need in this sector is knowing whether a listing is the original or a copy.

Working in a real agency, I noticed that it is often very difficult to tell whether a property advertisement is genuinely the original or simply a republished copy. It is extremely common to see the same property on several websites with the same photos and very similar information, but with small differences in price, description or contact person. The cause is structural rather than accidental: agencies publish to multiple portals at once, listings are distributed through XML feeds, and co-brokering agreements mean several agencies legitimately market the same property. The result is that the same flat can appear five or six times under different references and different prices.

This creates two concrete costs. For professionals it is repetitive manual work — opening tab after tab and comparing photographs and square metres by eye to work out whether two advertisements describe the same property. For buyers it means that if it is not clear who published first, they can end up dealing with an intermediary who only replicated the listing, which costs time and sometimes money.

I consider this need relevant rather than hypothetical for three reasons that come out of the sector analysis above. First, the duplication is a direct consequence of how the sector distributes listings, so it will not resolve itself. Second, the volume is significant — 850–900 agencies in the target area alone, all facing the same issue daily. Third, and most tellingly, the existing portals are the natural place this problem might have been solved, and they explicitly do not solve it: they neither date-rank nor group duplicate advertisements.

Two secondary needs appear alongside the main one: reducing the manual data-entry and comparison work that agencies currently absorb, and increasing transparency about who actually manages a property. Both are addressed by the same solution, which is part of why the product is worth building as one tool rather than three.

## 2.4. Foreseeable business opportunities in the sector

In addition to studying the current situation, you must analyze possible future opportunities.

You can consider technological changes, new forms of consumption, digital transformation, artificial intelligence, automation, cloud services, mobile devices, new business needs or any other trend directly related to your project.

The goal is to explain whether there is a reasonable opportunity for a solution like the one you propose.

Avoid overly general statements. Relate the opportunities identified to your specific project.

**ANSWER**

Several trends make a solution like FirstListing more viable now than it would have been a few years ago.

Several trends make a solution like FirstListing more viable now than it would have been a few years ago. The single biggest enabler is that language models have become cheap enough to use as an ordinary component rather than as a research project. The description comparison in FirstListing costs roughly $0.0003 per call using GPT-4.1-mini, and the entire project, across all development and testing, cost about 25 cents in API usage. Deciding whether two rewritten property descriptions describe the same flat is very hard to solve with string matching and straightforward for a language model. That capability existing at that price is what makes the product possible at all.

The Spanish real estate market is also extraordinarily disorganised. That is unhelpful for the people working in it, but it is precisely what creates the opportunity, since a tidier market with a single authoritative listing registry would not need this tool. On top of that, agencies are gradually adopting more software, which lowers the barrier to selling them another subscription tool, particularly to the younger back-office staff who are already comfortable with web applications.

The opportunity can be quantified rather than asserted. At 30 € per month, one agency pays 360 € per year. In the target market of 850–900 agencies on Costa Blanca South, an adoption of 10 % would mean about 90 agencies and roughly 32,400 € of annual revenue, while an adoption of 25 % would mean about 225 agencies and roughly 81,000 €. These are modest, specialised B2B numbers rather than mass-market ones. A realistic market share for FirstListing is small, because it is a specialised tool rather than a consumer product. But even low adoption produces meaningful revenue, and duplicate detection on its own already provides clear value, which is what makes the project worth continuing in its current direction.

At national level the addressable market is only a fraction of all Spanish agencies, but it still represents several thousand potential professional users. Internationally the same problem exists, but expansion would have to happen country by country, because each market has different portals and a different structure. Any future change of direction is therefore more likely to be an extension of the concept than a complete pivot.

## 2.5. Type of project required to respond to the anticipated demands

Once the need has been identified, you must justify what type of project is appropriate to solve it.

For example:

- web application;
- cross-platform application;
- mobile application;
- desktop application;
- video game;
- network service;
- client-server platform;
- management system;
- API or web service;
- a solution that combines several of the above.

Explain why you have chosen that solution and not another.

You can answer questions such as: does it need to work from a browser? does it require installation? must it work on several devices? does it need to store data? will there be different types of user? does it need a server? must it integrate with other systems?

**ANSWER**

The appropriate solution is a client–server web application. The questions that determine this lead there one by one.

The tool has to work from a browser, because the users are agents and back-office administrators who already spend the day in a browser, moving between portals and their own systems, and the tool has to sit where the work already happens. It must not require installation, since asking a small agency to install and maintain desktop software would be a serious adoption barrier for an audience that is only moderately tech-savvy. It also has to be usable from any office computer regardless of operating system, and a browser-based application gives that for free.

It needs to store data, and heavily. The entire premise is comparing a new listing against everything seen before, which is impossible without persistent storage. Roughly 95 % of this project is database logic, so the database is not a supporting feature, it is the product. There are different types of user, with three roles implemented: agent, private and admin. A server is also needed, because crawling and AI calls must run server-side: the API key cannot be exposed to the client, and browsers cannot crawl third-party sites because of cross-origin restrictions. Finally, the system has to integrate with other systems, namely the OpenAI API and the third-party websites it reads.

A mobile application was rejected because this is desk work done on a computer, not on the move, and it would add app-store distribution overhead for no benefit. A desktop application was rejected because of installation, updates and cross-platform cost. A pure API or web service was rejected because the target users are not developers and need a visual interface. A management system in the classic sense does not fit either, because the value here is in the analysis rather than in record-keeping.

The chosen architecture is therefore a web application with a server-side processing pipeline, an administration panel, and a browser front-end, which is also, conveniently, the architecture that best demonstrates the range of skills the course covers.

## 2.6. Specific characteristics of the project according to the requirements

Transform the needs identified into specific characteristics of the project.

For example, if users need to access it from any device, one characteristic could be having an adaptable web interface. If they need to retain information, the project must incorporate data persistence. If there are different profiles, it will be necessary to implement authentication, authorization and permission management.

Try to differentiate between:

- functional requirements: what the system must do;
- non-functional requirements: security, performance, usability, compatibility, availability, accessibility, maintainability, etc.;
- technical or external constraints.

This section should allow understanding of what the project will have to fulfill in order to truly respond to the need identified.

**ANSWER**

On the functional side, the system must allow user registration and login with passwords stored hashed, and support three user roles: agent, private and admin. User and admin areas are restricted to authenticated sessions. A logged-in user can submit a listing URL, which the system crawls, storing the raw page content. The structured fields are then extracted from that raw page using AI, namely price, surface area, rooms, bathrooms, property type, listing type, address, reference and description. Every stored listing is scored against the submitted one using a weighted SQL comparison, and the descriptions of the strongest candidates are compared using AI to catch rewritten text. The candidates are presented ranked, with their score, and each user's searches are recorded per month. The system also provides an admin panel with CRUD over the stored data, a raw-crawl viewer and a live crawler log, an interface in English and Spanish via a toggle, an AI chat widget that answers questions about the project, and a published Privacy Policy and Legal Notice. These fourteen requirements are referred to later as RF-01 to RF-14, in the order in which they have just been described.

On the non-functional side, security means that all database access goes through PDO with parameterised queries, passwords are hashed with bcrypt, and authentication is session-based with an access check at the top of every protected page. For performance and cost, the pipeline runs synchronously while the user waits, so it must complete within a reasonable time, and a 120-second limit is set for the request. The cost per run must also stay low, which is why the expensive AI description comparison runs only on the top five SQL candidates rather than on every match. For usability, the audience is only moderately tech-savvy, so the flow must be a single input and a clear result table, with no configuration to understand before getting a first answer. The application must be compatible with a normal desktop browser with no plugins. It must be available in English and Spanish, because the target market serves foreign buyers and the staff using it are frequently not native Spanish speakers. Maintainability means readable, commented code with a clear folder structure, which is a functional requirement in disguise: the crawler and parser are known to need ongoing adjustment, so code that is hard to change is code that will break permanently. Finally, for data protection, only the minimum necessary data is collected and how it is processed is documented.

The technical and external constraints are the following. The system depends on the OpenAI API, so if it becomes unavailable or changes price, the core feature is directly affected. Extraction depends on the structure of third-party websites, which can change without warning. Crawling requires permission and polite behaviour, which limits how many real sites can be used and is the reason a demo portal had to be built for testing. And the project runs on a local development stack rather than a production server, so anything requiring a live public deployment is outside what could be demonstrated.

## 2.7. Tax, labor and occupational risk prevention obligations, and conditions of application

Analyze what obligations would be applicable if the project were developed in a real professional context.

There is no need to turn this section into a legal treatise. You must identify the obligations that are related to your case and briefly explain how they would affect the project.

Depending on the project, there may be aspects related to:

- business activity and invoicing;
- hiring of staff;
- occupational risk prevention;
- intellectual property and licenses;
- data protection;
- terms of use of third-party services;
- other obligations specific to the activity.

If any issue is not applicable, you may state so and justify it.

**ANSWER**

If FirstListing were run as a real business rather than as an academic project, several obligations would apply.

On business activity and invoicing, the founder would have to be registered before invoicing clients, issue proper invoices for the subscription, and meet the corresponding periodic tax filings. Because the product is a recurring subscription rather than a one-off delivery, invoicing would be periodic rather than per project. In the company design described in 2.2, tax management and legal advice are outsourced from the very beginning, precisely so these obligations are handled correctly rather than improvised by a developer.

On the hiring of staff, in the realistic one-person version there are no employment obligations at all. They would appear only in the scaled 5–7 person version, at which point employment contracts, social security registration and payroll become relevant.

Occupational risk prevention also applies. This is a software business, but it is not risk-free. The relevant risks are electrical, since a badly installed 220 V connection can cause serious injury or fire and low-voltage devices can cause minor shocks, and, much more significantly, ergonomic. Long hours in front of a computer cause back pain, eye strain, carpal tunnel syndrome and burnout, all of which can lead to sick leave and financial loss. The preventive measures are safe cable installation, an ergonomic workspace and structured breaks. In a company with employees these would have to be formalised in a written risk assessment and prevention plan rather than left to good habits.

As for intellectual property and licences, the project's own code is mine. The development stack of Python, PHP, MySQL and Apache is free and open source, so no licence is purchased, but the licences still exist and their terms still apply.

Data protection is the obligation with the most direct effect on the product itself, because GDPR applies. FirstListing collects only the minimum necessary data: username, optional email, a bcrypt-hashed password, and the URLs searched. The legal basis for processing is performance of a contract (GDPR Art. 6.1.b). A Privacy Policy and a Legal Notice are published on the site covering legal bases, retention, erasure rights and third-party processors, with OpenAI identified as the sole third-party processor. The GDPR was reviewed specifically to confirm that the application's data handling is compliant.

Two sets of third-party terms of use apply. The OpenAI API has its own usage terms, which the project must respect. More importantly, crawling third-party websites is governed by those sites' terms, which is why crawling permission for the primary test site was obtained directly from the site owner, and why the crawler identifies itself with a polite User-Agent and waits 1.5 seconds between requests.

Finally, no sector-specific real estate regulation applies beyond standard GDPR, because the tool is aimed at private B2B users, does not interact with public administrations, and does not handle transactions or hold client money.

## 2.8. Possible aid or grants for the incorporation of new technologies

Research whether a project of these characteristics could qualify for aid, digitalization programs, entrepreneurship, innovation or the incorporation of new technologies.

It is not essential that a specific applicable aid exists. If you do not find a suitable one, state so with reasoning.

The important thing is to demonstrate that you have considered the possibility of financing or external support.

**ANSWER**

A project of this type — a digital product created by a new micro-enterprise, incorporating artificial intelligence — is in principle exactly the kind of activity that public digitalisation, entrepreneurship and innovation programmes exist to support. A real launch should therefore begin by checking what is available at national level, at regional level in the Comunitat Valenciana, and locally, as well as any support offered through the education centre itself.

However, I have not found a specific programme and confirm that FirstListing would qualify for it.

What I can say is that the need for external funding is genuinely small. As set out in 3.7 and 4.7, the only unavoidable cash costs in the first year are hosting, a domain and the API, which together amount to a few hundred euros. The dominant cost by a wide margin is my own time. That has two consequences for this section: any aid would shorten the runway rather than decide whether the project happens at all, and the most useful form of support would be one that reduces the cost of becoming self-employed, rather than one that subsidises equipment purchases — since no equipment needs buying.

> **This section needs real research before submission — see item 1 in the manual review list at the end of this document.**

## 2.9. Work outline for developing the project

Close this first phase by explaining, in general terms, how you are going to approach the project.

This outline will be an initial view of the work that you will later develop in greater detail in the planning.

Indicate the major stages planned: analysis, design, environment setup, implementation, testing, documentation, deployment, evaluation, etc., adapting them to your project.

**ANSWER**

The description of how this project was approached is that I started from the ground up rather than following a strict predefined plan. That was probably not the most time-efficient approach, but it made sense given the nature of the work: roughly 95 % of the project is database logic, so that is where I started.

The work began with analysis and paper design, sketching the database structure by hand, including how the components relate to each other and what data needs to flow where. Next came the environment setup, with a local Apache and MySQL through Homebrew, VS Code, Python 3, PHP, a Git repository pushed to GitHub, and an OpenAI API account. The core data layer followed, with a first SQL schema and then a second and final version once the structure was right.

Then came acquisition, the crawler, which went through five successive versions before one worked reliably. Extraction, the parser, needed two or three major iterations and ended with the OpenAI API, which was the only approach that worked effectively. After that I built the comparison logic, the weighted SQL scoring pass with the AI description comparison layered on top of it, and the test data, a demo portal of five fake listing pages, built because only one real site could legally be crawled. The front-end, in HTML, CSS and vanilla JavaScript, plus the admin panel, was deliberately built last. The final steps were refinement, which meant cleaning the code, cutting token usage and improving the interface, and then documentation and delivery, with the written documentation, the GitHub repository, the public presentation and the final delivery.

Rather than moving linearly through these stages, most of the work happened in continuous iteration between the crawler, the parser and the comparison logic, which were developed against each other rather than in sequence. Problems in the crawler only became visible when the parser ran against its output, and problems in the parser only became visible when the scoring ran against its output. The detailed timing is set out in 3.3 and the dependency reasoning in 4.1.

------------------------------------------------------------------------

# 3. Project design

The second learning outcome consists of **designing the project by explicitly developing the phases that make it up**.

In this part we are no longer so focused on justifying that a need exists, but rather on concretely defining the solution we intend to develop and verifying that it is viable.

**ANSWER**

This section defines the solution that was actually built. It describes the parts that make up FirstListing, shows why the project was achievable with the knowledge, tools and time I had, and sets out its phases, objectives, limits, resources, costs and quality criteria. The project has already been built and is still being developed, so the section is written looking back: it describes the design that was really followed, and where the design changed along the way I say so instead of presenting the current state as if it had been planned from the start.

## 3.1. Information relating to the aspects to be addressed in the project

Describe in detail the elements that will make up your solution.

You can explain:

- users or profiles;
- main modules;
- functionalities;
- general architecture;
- information that the system will handle;
- client and server components;
- external integrations;
- authentication mechanisms;
- main interfaces;
- planned deployment.

The goal is to break the general idea down into parts clear enough to be able to plan their development afterward.

**ANSWER**

FirstListing is a client–server web application. The browser only handles the interface, and everything that matters happens on the server: the crawling, the AI calls and the scoring. This is not a style choice. The OpenAI key has to stay private, and a browser cannot crawl third-party websites because of cross-origin restrictions.

There are three kinds of user, stored as a role in the database: agent, which is the default and the main commercial target, private, and admin. Agents and private users reach the dashboard and run duplicate checks. Admins can also enter the administration area. Registration stores a bcrypt hash of the password, and login opens a PHP session with the user id, username, email and role. Every protected page checks the session at the top and redirects at once if it is missing.

When a user submits a listing URL, the system runs four steps one after another while the user waits. First, a Python crawler downloads the page and stores its raw HTML. Second, a PHP script sends that raw page to GPT-4.1-mini, which extracts the structured fields (price, surface area, rooms, bathrooms, property type, reference and description) and saves them in the listings table. Third, an SQL query scores every stored listing against the new one, giving 5 points for the same reference, 3 for price, 3 for surface area, 2 for rooms, 2 for bathrooms, 1 for property type and 1 for listing type, so the maximum is 17. Candidates above the cut-off are returned in order of score. Fourth, the five best candidates that have a description are sent to GPT-4.1-mini, which judges whether the texts describe the same flat even when they have been rewritten.

This two-stage design is the main cost control in the system. The cheap SQL pass narrows the field first, and the expensive AI pass is only used on what is left. Doing it the other way round would give the same answers at many times the cost.

Around this pipeline sit the rest of the modules. The web application covers registration, login, the dashboard, the results and the public pages. The admin panel offers full CRUD over the data, a viewer for the AI-extracted listings, a viewer for the raw crawls and a live crawler log that the page refreshes with JavaScript. A chat widget answers visitors' questions about the project using the OpenAI API, and a language toggle switches the whole interface between English and Spanish. The client side is HTML5, CSS3 and vanilla JavaScript with no frameworks.

The data lives in six MySQL tables: raw_pages for the crawled HTML, ai_listings for the extracted fields, users, subscriptions, search_usage for the searches each user makes per month, and vector_matches. That last table is left over from the original plan to use a vector database, which I dropped (see 5.5), and it is no longer used. The only external integration is the OpenAI API, used for field extraction, description comparison and the chat widget.

The application was developed and demonstrated on a local Apache and MySQL stack installed with Homebrew on macOS, but it is designed so it could be moved to a normal server, such as the Windows Server used in class.

## 3.2. Technical feasibility study

You must justify that the project can be carried out with the knowledge, technologies, resources and time available.

A final project must be sufficiently complete to demonstrate the skills acquired, but it must also be achievable.

Analyze, among other aspects:

- technical complexity;
- necessary technologies;
- available knowledge and knowledge that will need to be acquired;
- availability of hardware and software;
- necessary external services;
- limitations;
- technical risks;
- available time.

Do not propose a project whose scope is impossible to take on. It is preferable to properly develop a product with a reasonable scope than to propose an enormous system that ultimately cannot be completed.

**ANSWER**

The project is harder than a typical CRUD exercise, but the difficulty is concentrated in two places, the crawler and the parser. The rest is database logic and a web interface, which is well inside what the course covered. This helped feasibility because the risk was easy to identify and could be tackled first.

Most of the knowledge was already in hand. PHP, MySQL, HTML, CSS and JavaScript were taught during the course, and I already knew Python. The only really new skill was using a language model API and writing prompts that return reliably structured data, which I learned during the project. That gap was the right size: big enough to be worth learning, small enough not to put the delivery at risk.

The hardware and software needed were also already available. Everything was built on my own MacBook Pro M3 with a local Apache and MySQL stack, and every tool I used (VS Code, Python, PHP, MySQL, Git) is free, so I never had to buy or rent a server.

The one paid service is the OpenAI API, and it is the key point for feasibility. A conventional web project runs on a small server with 2–4 GB of RAM and 60–80 GB of storage for roughly 3.60–20 € a month. Adding AI can change that a lot if the models run locally, because they often need GPUs, monthly costs can reach 20–50 €, and professional hardware such as an RTX 4090 costs about 250 € or more. By using a hosted API and calling it sparingly, the project stays in the first group. Each description comparison costs about $0.0003, and the whole project cost about 25 cents in API usage. That is also why the comparison has two stages: cheap filtering in SQL first, AI only on the narrowed list, and data analysed once and stored rather than processed again on every request.

The main limitations are the dependence on the HTML of third-party sites, which can change without notice, and on the OpenAI API staying available and affordable. Only one real site could legally be crawled, so I built a demo portal of fake listings for testing.

As for time, the first stage took about two and a half months, roughly 1.5 hours a day on the days I worked, against two fixed deadlines. I limited the scope to what fitted in that time, which meant a working duplicate-detection pipeline instead of a full commercial platform, and I left image-based matching out for that reason. The project was feasible, and that first version was finished on time for both deadlines. The work has continued in the second year, which gave me more time to improve it.

## 3.3. Project phases, content and execution deadlines

Divide the project into clearly identifiable phases.

For each phase, indicate:

- what it aims to achieve;
- what tasks it contains;
- what result or deliverable it will produce;
- when it will start;
- when it should finish;
- which previous phases it depends on, where applicable.

You can represent this information by means of a table, timeline or Gantt chart.

The planning must be consistent with the actual time available.

**ANSWER**

The project began at the end of January 2026 and had its first delivery on 25 March 2026. Work then started again in the next school year, and it is still going on at the time of writing, in November 2026. The first stage had one fixed checkpoint in between, the public presentation on 27 February 2026. The phases below describe that first stage, and the second stage is described at the end of the section.

The work started with two preparation phases in the first weeks. The first was analysis and data design in weeks 1–2, where I sketched the schema on paper, worked out how the components relate and decided what data had to flow where. The result was the paper design and a first SQL schema. In week 1 I also set up the environment (Homebrew Apache and MySQL, VS Code, Python, PHP, a Git repository on GitHub and an OpenAI account), which gave me a working development stack. Neither of these depended on earlier work.

The core of the project was three phases that overlapped over weeks 1–7. The crawler had to fetch and store listing pages reliably, and it went through five versions with a single-URL mode and a sitemap mode, a polite User-Agent and a 1.5 second delay. It depended on the design and the environment, and its result is python/crawler_v4.py. The parser extracts the structured fields from the stored HTML, and after two or three major iterations the final version uses the OpenAI API, giving scripts/openai_parse_raw_pages.php. The database refinement produced the second and final schema, data/sql/test2firstlisting.sql, once the real extracted data showed what the first one was missing. I worked on these three phases in constant iteration instead of one after another, because a fault in one only appeared when the next one used its output.

In weeks 5–7 I built the comparison logic, which depends on the parser and the final schema. It is made of the weighted SQL scoring and the AI description comparison on the best candidates, and it lives in scripts/find_duplicates.php and scripts/ai_compare_descriptions.php. Around week 6 I also built the test data, five fake portal pages and a seeding script in data/demo-portal/, so that there were predictable and varied cases to test against.

All of this had to be ready for the presentation on 27 February, where I gave a live demo of the pipeline instead of showing slides. By about 20 February, three weeks into the project, the core pipeline already ran end to end in rough form, with a basic crawler, a basic parser and a first version of the MySQL database. Nothing was polished, but it worked, and that was what I took to the presentation.

After the presentation I built the front-end and admin panel over weeks 7–10, using HTML, CSS and vanilla JavaScript for the dashboard, the admin area and the language toggle, all inside public/. From then until the delivery the work was refinement and not new features: cleaning the code, reducing token usage, improving the crawler and parser, and upgrading the interface. The last part of the first stage, up to 25 March, was documentation and delivery, with the written report, the GitHub repository and the legal pages.

The second stage started with the new school year and is still running. Its aim has not been to add large new features but to smooth out and streamline what already existed, with updates to the code, the interface and the documentation, and a first version of this final report. It depends on everything above, because it works on the finished first version, and more updates are planned. This means the project is no longer a closed delivery but a product that keeps improving, and the improvements I still plan are listed in section 6.

## 3.4. Objectives and scope

Define the specific objectives you intend to achieve.

The objectives must be verifiable. Instead of writing only "create a good application," it is preferable to establish objectives such as "implement an authentication system with two user profiles," "allow CRUD operations on the main data" or "deploy the application on a server accessible via browser."

You must also define the **scope**: what the project includes and what is explicitly left out.

Defining the boundaries is important to prevent the project from growing uncontrollably during its development.

**ANSWER**

The objectives are written so that each one can be checked on the delivered system. The project had to provide authentication with three user roles, which I check by registering and logging in as agent, private and admin and confirming that non-admins are rejected from the admin area. It had to store all data in a relational database, which I check by finding the six tables present and filled after a run. It had to crawl any listing URL and keep its raw content, checked by submitting a URL and finding the new raw page row, and extract structured fields from the raw HTML with AI, checked by the listings row with price, surface area, rooms, bathrooms, type and reference.

It also had to score the stored listings against a submitted one with weighted SQL, checked by seeing the candidates ordered by score against the 17-point maximum, and compare descriptions with AI for the best candidates, checked by rewriting a description and confirming the listing is still matched. The results had to appear ranked in a usable interface, which I check by submitting a URL and reading the table, and there had to be an admin panel with full CRUD, checked by creating, reading, updating and deleting records. The interface had to exist in at least two languages, checked with the EN/ES toggle.

Three more objectives concern how the project is run. The AI cost per run had to be low enough to be commercially plausible, and I measured token use per run at 2,000–5,000, down from 15,000–20,000. The project had to be under version control with a visible history, which the public GitHub commits show. And it had to comply with GDPR in the data it collects, which I check through the published Privacy Policy and Legal Notice, the minimum data collected and the hashed passwords. In total there are twelve objectives, which I refer to later as O-1 to O-12 in this order.

The scope includes the full pipeline from URL to ranked duplicate list, the three user roles, the user dashboard, the administration panel with CRUD, raw-data viewer and live crawler log, the bilingual interface, the AI chat widget, the legal pages, the demo portal of five fake listings, and the documentation and public repository.

I left several things out on purpose, and that is what kept the project deliverable. Image-based matching was considered at the start and dropped, because it would add a lot of complexity and cost for a benefit that text and number comparison already mostly gives. The system also does not know real publication dates. It orders listings by when the crawler first saw them, and the portals do not reliably show the real date, so this is the most important limitation to state honestly, since "which came first" is the headline claim of the product. I did not crawl live portals at scale, because only one real site could legally be crawled. I did not deploy to production, so firewall configuration, automated backups and server hardening were not done. A subscriptions table exists, but I built no payment or billing, and I did not build a mobile app, for the reasons in 2.5.

## 3.5. Activities necessary for development

Identify the activities necessary to be able to carry out the project.

Do not necessarily confuse a preparatory activity with a functional phase of the software.

For example:

- prepare the development team;
- install tools;
- set up a repository;
- hire or prepare a server;
- register a domain;
- create accounts for external services;
- prepare initial data;
- configure testing environments;
- obtain graphic or multimedia resources.

Indicate only those activities that are relevant to your project.

**ANSWER**

Before writing any code I had to prepare the working environment. I used my own MacBook Pro M3, so no hardware was bought. I installed Apache and MySQL with Homebrew, together with PHP and Python 3, so the application could run locally with no external server, and I installed VS Code and Git. I created a Git repository and pushed it to GitHub, which the course also required. I opened an OpenAI account and got an API key, which I keep in a local environment file outside version control. I then wrote and applied the SQL schema, and revised it once when the first structure turned out to be insufficient.

Some activities were prerequisites and not formalities. I asked the owner of the main test site for crawling permission and received it, and without it I would have had no real site to test against. I then built the demo portal, five fake listing pages, because a single real site cannot produce the varied near-duplicates the system is meant to detect. Finally I wrote a one-off script that fills the database with known duplicates, so the scoring could be checked against expected results instead of guessed.

Some usual preparatory activities did not apply: I did not rent a server, register a domain or buy any licence, and the only graphic resource I needed was the company logo, which I generated with AI tools.

## 3.6. Material and personnel resources required

List the resources needed to develop the project.

### Material and technical resources

For example:

- computers;
- servers;
- mobile devices;
- peripherals;
- hosting;
- domain;
- software;
- operating systems;
- development environments;
- databases;
- external services;
- licenses.

Even if you use free or open-source software, it is still a resource and should be identified.

**ANSWER**

The development computer was my own MacBook Pro M3, which I already owned and which would cost about 1,500 € to buy new, and it runs macOS. There was no server, because Apache runs locally through Homebrew, so the cost during development was 0 €. The web server (Apache), the database (MySQL/MariaDB), the languages (PHP and Python 3), the editor (Visual Studio Code) and version control (Git, with the repository on GitHub) are all free or open source. The only paid resource is the OpenAI GPT-4.1-mini API, at about $0.0003 per call and about 0.25 € across the whole project. I used no hosting or domain during development, which would cost 500–1,000 € a year in a real launch, and the logo was made with AI tools at no cost.

Free software is still a resource, because it has to be installed, configured and maintained, which is why I list it even though no money was spent. In a commercial launch there would also be recurring costs for hosting and cloud services (500–1,000 € a year) and for internet, electricity and software tools (about 250 € a month, or 3,000 € a year). With telework there is no rent, so the material cost of the first year would be around 7,000–7,500 €.

### Personnel resources

Indicate what professional profiles would be necessary if the project were carried out in a real business context: development, design, systems, testing, project management, support, etc.

If the academic project is carried out by a single person, you may state so, but you must be able to analyze what human resources an equivalent professional execution would require.

**ANSWER**

In practice the project needed exactly one person, and I did every role: analysis, database design, crawler, AI integration, backend, frontend, admin panel, testing, documentation and the public presentation.

A real company would need several profiles to build the same product. A project manager or Product Owner, part-time, would set the scope and priorities and handle the client. A full-time backend developer would build the web application, the database access and the pipeline. A data engineer would build the crawler and the parser, which are the hardest and most fragile parts, and would be needed full-time during the acquisition work. A part-time frontend developer would do the interface, admin panel and translations. A database designer would be needed briefly at the start, a part-time QA tester throughout, and a part-time systems or DevOps person mainly at deployment. Legal and tax advice would be outsourced. In the micro-enterprise version from 2.2, one founder covers the first seven of these and only the legal and tax work is outsourced.

Doing this exercise is useful even for an individual project, because it shows where the work really is. The data engineer role covers almost the whole project and owns the two most fragile components, while the frontend work is squeezed into the last stretch. That is why the first extra hire in a growing company should be a second data engineer and not a frontend developer. For costing I assume one full-time person at a gross annual cost of 24,000 €, the minimum realistic personnel expense even in a one-person company.

## 3.7. Financing needs

Estimate whether financing would be necessary to launch the project.

Consider costs such as:

- hardware;
- licenses;
- hosting;
- domains;
- external services;
- publishing on platforms;
- hiring professionals;
- promotion;
- initial maintenance.

If the project can be launched with a small investment, explain it. If it does not need external financing, state why as well.

**ANSWER**

I did not need any financing to build the project. I used hardware I already owned, free and open-source software, a local stack with no server or domain, and the only external cost was about 25 cents of API usage.

Launching it commercially would need money, but not a lot. The realistic cost of the first year is about 31,500 €: a gross founder salary of 24,000 €, computer equipment of 1,500 €, hosting and cloud services of 500–1,000 €, and 3,000 € for internet, electricity and software tools. There is no rent because of telework, and the OpenAI cost is negligible at the current volume.

At 30 € a month each client pays 360 € a year, so about 88 paying clients are needed to break even. That is about 10 % of the 850–900 agencies in the target area, which is ambitious for a first year. A more realistic 40–60 clients would bring in 14,400–21,600 €, so the first year would probably make a loss if I drew a full salary.

The salary is nearly all of the cost, though, and it is also the most flexible part. I would not work on this full time, so I would not need a full salary from it. Without the salary the real cash requirement is about 7,500 €, which is small enough to pay myself or cover with a modest amount of external support. The project therefore does not depend on raising capital. It depends on how much unpaid time I am willing to invest before revenue arrives, which is a risk I can actually control.

## 3.8. Documentation needed for the design

Indicate what documentation you need to consult or prepare in order to properly design the project.

It may include:

- requirements;
- technical documentation for languages and frameworks;
- API documentation;
- data models;
- diagrams;
- functional specifications;
- applicable regulations;
- licenses;
- manuals for external services;
- accessibility, security or data protection criteria where applicable.

The purpose of this section is to demonstrate that the design is not carried out merely by intuition, but is supported by appropriate information and documentation.

**ANSWER**

For the design I used the official documentation of the tools: the PHP, Python, MySQL and Apache documentation for syntax, standard library behaviour and local configuration, and the OpenAI API documentation for request formats, model choice and token counting. The token documentation in particular drove the cost optimisation. I also read the GDPR to check that the application's data handling is compliant, to choose the correct legal basis (Article 6.1.b) and to understand the obligations about retention, erasure and third-party processors, and I read the Spanish LSSI to know what the Legal Notice must contain.

An important and easily forgotten part of the design work was reading the HTML and JSON-LD of real listing pages to see what could reliably be extracted and what could not. The database schema was shaped by that reading and not by an idealised idea of what a property record should contain.

The documents I produced are the requirements and scope (this report plays that role, since there was no client brief, see 5.7), the database model, first on paper and then as an SQL schema, the design of the four-step pipeline with the scoring weights, and the Privacy Policy and Legal Notice published on the site. I also generated technical documentation of the code automatically from the project directory, with AI summaries of each file and folder, producing a Markdown report with the directory tree, all the source code and a summary of each component, which is published in the GitHub repository.

This shows that the design was not done by intuition. The two decisions that shaped the project most, using a hosted AI model instead of a self-hosted one and filtering in SQL before calling the AI at all, both came directly from reading cost and token documentation.

## 3.9. Aspects that must be controlled to guarantee quality

Define what you will understand by quality in your project.

You can establish criteria related to:

- compliance with requirements;
- correct operation;
- absence of serious errors;
- usability;
- accessibility;
- security;
- performance;
- compatibility;
- code quality;
- maintainability;
- documentation;
- tests carried out.

The indicators must be verifiable afterward.

**ANSWER**

For a one-person project of this size, a full Scrum or Kanban setup would have been too much. I managed quality with personal organisation, a paper notebook, Git and careful testing, and I judged it against the following criteria.

The first criterion is compliance with the requirements: every objective in 3.4 has a way of being verified, so I judge the project against that list and not against a general impression. The second is correct operation. After writing each function I tested it on its own and then as part of the whole system, and every major component (crawler, parser, SQL scoring and AI comparison) was checked both in isolation and in the full pipeline. Because only one real site could be crawled, I built the demo portal of five fake local listings so that the tests had known, predictable results. Without it, the question "does the matching really work?" would have had no objective answer.

Code quality and maintainability mean a clear folder structure, consistent names, commented code and parameterised database queries everywhere. Readability matters here because the crawler and parser will need constant adjustment. I used Git throughout and committed every meaningful change with a message, so I could review the history and go back after a mistake. My commit messages are not always as descriptive as they should be, and I want to improve that in future projects.

Cost is also a quality measure in this project, because an accurate system that is too expensive to run is not a viable product, so I reduced token use per run from 15,000–20,000 to 2,000–5,000. For security I used PDO with parameterised queries, bcrypt for passwords and a session check at the top of every protected page. The two fixed dates, the presentation on 27 February and the delivery on 25 March, acted as checkpoints that forced each phase to be working before the next one started. Finally, the documentation is a complete repository report with the directory tree, the code and a summary of each component. All of these criteria can be checked afterwards, and each one has a measured result in 5.2.

------------------------------------------------------------------------

# 4. Execution planning

The third learning outcome consists of **planning the execution of the project, determining the intervention plan and the associated documentation**.

You must now turn the previous design into an executable work plan.

**ANSWER**

This section turns the design into an executable plan: the order in which the tasks were carried out and why that order was forced by real dependencies rather than chosen, what each task needed in place before it could start, the permissions required, the working procedures used, the risks and how they were mitigated, how resources map onto the timeline, what the project would cost in a professional setting, and the documentation that supports execution.

## 4.1. Sequencing of tasks according to implementation needs

Break the project down into specific tasks and establish their order.

For example, in an application it might be necessary to first design the data model, then prepare the database, implement the business logic, develop the interfaces and finally integrate and test the whole.

In other projects some tasks may be carried out in parallel.

The sequence must respond to real dependencies, not simply to an arbitrary order.

**ANSWER**

The sequence was driven by genuine dependencies, not by preference. The core constraint is that every later stage consumes the output of an earlier one: you cannot extract fields from a page you have not fetched, you cannot compare listings you have not extracted, and you cannot display results you have not computed.

The sequential chain, in which each step blocks the next, starts with designing the data model. Everything downstream writes into it, so getting the structure roughly right first avoided reworking every other component later. Creating the database comes second, because the crawler needs somewhere to write raw pages before it can be tested meaningfully. The crawler is third and produces the raw HTML the parser consumes, so nothing downstream can be tested without real stored pages. The parser is fourth and converts raw HTML into structured fields, since the scoring logic is meaningless without populated, comparable rows. The SQL scoring comes fifth and produces the ranked candidate list. The AI description comparison is sixth and runs on the output of the scoring. It is deliberately last in the pipeline because it operates only on the narrowed candidate set, and that ordering is the cost control described in 3.1. The front-end is seventh and presents the results of the previous steps. It was built last, and correctly so, because there was no point designing a results table before knowing what the results actually looked like. The final step is integrating and testing the whole pipeline end to end.

Some of the work ran in parallel or iteratively. The crawler, the parser and the scoring were not completed once each and then left alone. They were developed against each other in continuous iteration, refining the parser, then the crawler, then both together, because faults in one only became visible when the next consumed its output. The crawler went through five versions and the parser through two or three before either was satisfactory. The demo portal was built alongside the crawler and the parser, as soon as it became clear that one real site was insufficient for testing. Documentation and Git commits ran continuously throughout, and refinement of the crawler, parser and interface continued after the presentation checkpoint, in parallel with each other.

The one deviation from a textbook order is that the database schema was revised once mid-project. That is less a planning failure than an acknowledgement that the right schema only became fully clear after the parser revealed what the extracted data actually looked like in practice.

## 4.2. Resources and logistics needed for each task

Relate the main tasks to the resources they need.

A deployment task may need a server; a mobile test may require devices or emulators; an integration may need API credentials; a design phase may need certain tools.

The question you must answer is: **what do I need to have ready in order to correctly execute each task?**

**ANSWER**

Each task needed certain things to be ready before it could start. Designing the data model needed only pen and paper and an understanding of what a real listing page contains, and creating the database needed the local MySQL running via Homebrew. Developing the crawler needed Python 3, a target site, written crawling permission from the site owner, and the raw pages table. The parser needed stored raw pages, an OpenAI account and API key, and the listings table. The SQL scoring needed a populated listings table with several comparable records and seeded test duplicates, and the AI description comparison needed the extracted descriptions, the candidate list from the scoring step, and API credentials. Testing the matching logic needed the demo portal of five fake listing pages with known relationships.

The front-end and admin panel needed local Apache serving PHP and working pipeline output to display, while the live crawler log needed the crawler to write to a log file the admin page can poll. Internationalisation needed finished interface text to translate. Version control and delivery needed Git installed and a GitHub repository created and connected, and the legal pages needed the GDPR and LSSI requirements to have been reviewed.

Two of these were genuine blockers rather than routine setup. The crawling permission had to be obtained before any real data could be collected at all. The demo portal had to exist before duplicate detection could be tested properly, because a single real site cannot produce the varied near-duplicates the system is designed to find. Both were resolved early enough not to delay the project, but neither was optional, and both are easy to overlook when planning because neither is a line of code.

Notably absent from this list: no server had to be rented, no domain registered, no paid licences acquired, and no external team coordinated. The logistics of this project were unusually light, which is one of the reasons the scope was achievable in the time available.

## 4.3. Permits and authorizations

Analyze whether any task requires permits or authorizations.

There may be, for example:

- permits to access a company's systems;
- authorization to use certain data;
- credentials for external services;
- permits to publish an application;
- consent to conduct tests with users;
- licenses to use resources.

If you do not need special permits, state so and justify it.

You must not claim that a specific authority must authorize a data processing operation unless that legal requirement genuinely exists for your case; what matters is correctly identifying the applicable obligations and permits.

**ANSWER**

The only permission the project genuinely needed was crawling permission, and it was obtained. Reading and storing pages from a third-party website is governed by that site's terms, so permission for the primary test site was requested from, and granted directly by, the site owner. Alongside the permission the crawler behaves politely: it identifies itself with a clear User-Agent and waits 1.5 seconds between requests, so it places no meaningful load on the site. This constraint is also why the demo portal exists. Rather than crawl sites I had no permission for, I built five fake listing pages to test against.

OpenAI API credentials were also required and obtained, through a standard commercial account. The API key is stored in a local environment file excluded from version control.

Data protection is a matter of obligations, not authorisations, a distinction that is easy to get wrong. No authority has to approve FirstListing's data processing, and there is no registration or prior authorisation requirement for processing of this kind. What exist are obligations under GDPR, which the project meets by collecting only the minimum necessary data (username, optional email, bcrypt-hashed password, and the URLs searched), by processing on the legal basis of performance of a contract (Art. 6.1.b), by publishing a Privacy Policy and Legal Notice covering legal bases, retention and erasure rights, and by identifying OpenAI as the sole third-party processor.

Several other permits are not required. No permission is needed to access any company's internal systems, because the project reads only public web pages. No app store or publication approval is needed, since this is a web application. No consent for user testing was needed, because no testing with real users was carried out (see 5.6). No paid licences are needed, since the entire stack is free and open source, and no sector-specific real estate authorisation applies, because the tool is a B2B analysis product aimed at private users, does not interact with public administrations, and does not handle transactions.

## 4.4. Procedures for carrying out the tasks

Explain what procedures you will use to work in an orderly manner.

For example:

- version control;
- branching strategy;
- backups;
- code review;
- naming conventions;
- version management;
- testing before integrating changes;
- deployment procedure;
- task logging;
- deliverable validation.

Not all projects need the same procedures. Select the ones that add value to your case.

**ANSWER**

For version control I used Git throughout, with the repository on GitHub. Every meaningful change was committed with a message, so the history can be reviewed and a broken change rolled back. I worked directly on the main branch rather than using a branching strategy, because with a single developer and no concurrent work, branches would have added ceremony without benefit. One weakness I will name openly is that the commit messages are not always as descriptive as they should be.

The GitHub remote is the backup. Pushing regularly means the project exists in at least two places at all times, which matters given that hardware failure is one of the identified risks. Secrets are handled by keeping the API key in a local environment file excluded from version control, so credentials never reach the repository.

The central discipline of the project was testing before integrating. After every function was implemented it was tested, first in isolation and then integrated with the rest of the system, and each major component was verified on its own and again as part of the full pipeline. This is the reason a fragile system that went through five crawler versions and three parser versions never ended up in an unknown state. The demo portal of five fake listing pages provided a known, repeatable set of inputs, so a change to the scoring could be judged against expected results rather than estimated by eye.

On naming and structure, I used consistent file and folder naming, with a clear separation between public/, scripts/, python/, config/ and data/, and commented code throughout. For task tracking I used a physical notebook rather than Trello or a project management tool. Writing down ideas, structures and what needed doing next was sufficient, and it was particularly useful in the early stages for sketching timelines and thinking through how the components should fit together. With clear deadlines, one developer and regular commits, a notebook and Git served the same purpose a formal tool would have. Deliverables were validated through the two fixed deadlines, the presentation on 27 February and the final delivery on 25 March, which acted as checkpoints, each requiring the system to be in a demonstrable state rather than merely in progress.

Some procedures were deliberately not used: there was no code review because there was no second developer, no formal branching strategy, no CI pipeline, and no formal release versioning. These add real value on a team, but on a solo project of this length they would have been overhead without benefit.

## 4.5. Risks inherent to execution and prevention plan

Identify the risks associated with the project and explain how you will reduce them.

In addition to the risks inherent to working with computer equipment — ergonomics, eye strain, electrical risks when handling hardware, etc. — you can consider risks specific to the project itself:

- loss of information;
- hardware failure;
- unavailability of services;
- configuration errors;
- security problems;
- delays;
- dependence on external technologies.

For each relevant risk you can indicate its probability, impact, preventive measures and planned response.

**ANSWER**

I identified twelve risks, which I refer to as R-01 to R-12. The first is that a target site changes its HTML structure and the crawler breaks. The probability is high and so is the impact. Prevention consists of keeping the crawler logic isolated and readable and avoiding over-fitting to one layout, and the response is to adjust extraction for that site, with the demo portal confirming that the rest still works. The second is that adding a new site is harder than expected, because of different structures, different amounts of raw text and different JSON-LD implementations. The probability is high and the impact medium. It is prevented by using AI extraction rather than per-site hardcoded rules, and the response is to tune the prompt and accept slower per-site onboarding.

The third risk is that the OpenAI API becomes unavailable or changes pricing. The probability is low but the impact is very high. AI calls are kept concentrated in two scripts so the provider can be swapped, and if it happens another provider would be substituted, since pure-code parsing is not a realistic fallback. The fourth is that token usage climbs and cost becomes unviable, with medium probability and medium impact. Prevention is to filter in SQL before calling AI, cap the AI comparison at the top 5 candidates and monitor tokens per run, and the response is to re-optimise prompts, which was already done once, from 15,000–20,000 tokens down to 2,000–5,000.

On security, the fifth risk is SQL injection, with low probability and high impact, prevented with PDO and parameterised queries everywhere, and answered by patching and reviewing all query construction. The sixth is session hijacking or unauthorised access, also low probability and high impact, prevented with session-based authentication checked at the top of every protected page and bcrypt password hashing, and answered by invalidating sessions and reviewing authentication.

The seventh risk is data loss or hardware failure, low probability and high impact, prevented by regular commits and pushes to GitHub and answered by restoring from the remote repository. The eighth is data growth and corruption, since stored HTML is currently 20,000–25,000 characters per page and could reach 100,000 if the crawler scope widened. Its probability and impact are both medium, it is prevented by keeping the crawler scope tight and storing only what is needed, and the response is to trim stored content and introduce retention limits. The ninth is a schedule slip against the two fixed deadlines, with medium probability and high impact. It is prevented through a deliberately limited scope and by leaving the front-end until last so the core pipeline was proven first, and the response is to cut scope rather than move the deadline, which is why image matching was excluded.

The last three are the risks inherent to working with computer equipment. The tenth is electrical risk from badly installed cabling, with low probability and high impact, because a 220 V connection can cause serious injury or fire and low-voltage devices can cause minor shocks. It is prevented by safe cable installation and answered with standard electrical safety and first aid procedure. The eleventh is ergonomic risk, meaning back pain, eye strain, carpal tunnel syndrome and burnout, with high probability and medium impact. It is prevented with an ergonomic workspace, correct screen height and regular structured breaks, and the response is to adjust the workstation and reduce session length, since untreated these lead to sick leave and financial loss. The twelfth is loss or theft of the development machine, low probability and high impact, which is covered by the remote repository holding the code and answered by restoring from GitHub onto replacement hardware.

The two weakest points in the system are the crawler and the parser, since they are by far the most complex parts of the project. R-01 and R-02 are therefore the risks most likely to actually materialise, and they would be the main scaling problem if the project grew beyond its current set of sites. R-03 is the opposite profile: unlikely, but it would break the core feature outright, which is why it is worth naming even though nothing in the current design mitigates it fully.

On IT security specifically, because the project runs locally rather than on a live server, measures such as firewall configuration and server updates are not directly applicable at this stage. If the project were deployed to production they would become standard requirements: keeping the operating system and dependencies updated, configuring a firewall, and setting up regular automated backups.

## 4.6. Allocation of material and human resources according to execution timing

Relate the time planning to the resources.

Indicate what resources are needed in each phase, for how long, and which professional profile would be responsible in a professional scenario.

Even though the academic project is individual, this exercise allows you to demonstrate that you are capable of planning the project from a professional perspective.

**ANSWER**

In the first two weeks, analysis and data design would be handled by a database designer together with a Product Owner, using pen and paper and a computer. The environment setup in week 1 would fall to DevOps / systems, with the MacBook, the Homebrew stack, VS Code, Git, GitHub and the OpenAI account. The crawler (weeks 1–7) belongs to a data engineer, who needs Python 3, a target site with permission and MySQL. The parser (weeks 1–7) belongs to a data engineer with AI integration skills, using PHP, the OpenAI API and the stored raw pages, and the database refinement over the same weeks belongs to a database designer working in MySQL.

The comparison logic (weeks 5–7) would be the work of a backend developer using PHP, MySQL and the OpenAI API. The demo portal and test data, around week 6, would be done by a QA / tester with HTML and the seeding script. The front-end and admin panel (weeks 7–10) belong to a frontend developer working in HTML, CSS and JS on the local Apache. After 27 February, the refinement and optimisation would involve a backend developer and a data engineer on the full stack. Documentation and delivery, up to 25 March, would be handled by a technical writer and the Product Owner using the documentation tooling and GitHub, and legal and compliance throughout would be an outsourced legal adviser working from GDPR and LSSI reference material.

In reality one person, me, filled every one of these roles. The exercise of assigning professional profiles is still worth doing, because it makes visible where the workload actually concentrates: the data engineer role spans almost the entire project and owns the two most fragile components, while the frontend role is compressed into the final quarter. In a real team that is where the staffing would go, and it is also why a second hire in a scaled company would sensibly be a second data engineer rather than a frontend developer.

I have not estimated the total hours of effort. The work was done in two separate periods with a long break between them, so a figure based on hours per day would not mean anything. What matters for the plan is that the first version took about two and a half months, and that the second year has been spent improving it.

## 4.7. Economic assessment

Carry out a reasonable estimate of the project's cost.

You must consider both material and human resources.

Do not assume that the cost is zero simply because you already have a computer or because you are carrying out the project as students. The goal is to estimate how much it would cost to develop an equivalent solution in a professional context.

You can consider:

- depreciation or acquisition of equipment;
- licenses;
- servers and services;
- domains;
- publishing;
- graphic or multimedia resources;
- working hours of the necessary professional profiles;
- other directly related expenses.

Explain the criteria used to carry out the calculations.

**ANSWER**

The figures below assume the project is developed in a professional context rather than as a student exercise, so the costs are the ones a real launch would have, even though no money changed hands. I have not put a price on the hours I worked, because the project was done in two separate periods with a long break between them and the total time cannot be measured reliably. The only cost of my own time that I include is the founder salary below. Equipment is costed at acquisition value rather than amortised. Hosting and utilities are included at realistic market rates even though the project actually ran locally at zero cost.

The first-year operating cost is made up of the founder salary (gross), at 24,000 €, which is the minimum realistic annual personnel expense; computer equipment (a MacBook at acquisition value), at 1,500 €; hosting and cloud services, at 500–1,000 € annually; and internet, electricity and software tools, at about 250 € per month or 3,000 € per year. Office rent is 0 € because of telework, and the OpenAI API, at about $0.0003 per comparison call, is negligible at current volume. The materials subtotal is approximately 7,000–7,500 €, and adding the salary gives a total first-year cost of approximately 31,500 €.

For revenue and break-even, at 30 € per month each client pays 360 € per year, so 31,500 € divided by 360 € gives about 88 clients. Break-even at roughly 88 clients, about 10 % of the market, brings in around 31,680 € a year and covers the costs. A realistic first year of 40–60 clients brings in 14,400–21,600 € and results in a loss, while 25 % penetration, about 225 clients, would bring in around 81,000 € and a comfortable profit.

The dominant cost is labour: salary alone is roughly 76 % of the first-year total. Actual cash spent on this project to date is close to zero, since the hardware was already owned, the software is free and open source, there is no server, and API usage cost about 25 cents.

The gap between 31,500 € and approximately nothing is entirely the value of my own unpaid time. That is the honest conclusion of the economic assessment: FirstListing is cheap to build and expensive to staff, and its viability in year one depends less on the market than on whether the founder needs to draw a full salary from it.

## 4.8. Documentation needed for execution

Indicate what documents will be needed during execution.

For example:

- requirements specification;
- data model;
- diagrams;
- planning;
- API documentation;
- deployment instructions;
- test plan;
- incident log;
- change control;
- technical documentation.

This is not about repeating the entire report, but about identifying the documentation that genuinely supports execution.

**ANSWER**

The documents that genuinely support execution, as opposed to restating this report, start with the requirements and scope definition: the functional and non-functional requirements in 2.6 and the objectives in 3.4, used as the checklist for deciding when something is finished. The data model is the SQL schema file, which is the authoritative description of the six tables and what every new component is written against. The pipeline specification describes the four ordered steps, what each consumes and produces, and the exact commands used to invoke them, since the web application calls the crawler and the scripts directly. The scoring specification records the field weightings and the cut-off, documented separately from the code because they are tuning parameters that are expected to change. The planning is covered by the phase table in 3.3 and the task sequence and dependencies in 4.1.

The OpenAI API documentation was consulted continuously during parser and comparator work, particularly for token accounting. Deployment and setup instructions explain how to install the local stack, create the database from the schema, and configure the API key, and are needed by anyone rebuilding the environment from scratch, including me on a new machine. The test plan and test data consist of the demo portal pages and the seeding script, together with a record of which listings are expected to match which. The incident and change record is the physical notebook and the Git history (see 5.3 and 5.5). The technical documentation of the codebase was generated automatically from the project directory with AI summaries of each file and folder, producing a Markdown report containing the full directory tree, all source code and inline summaries, and it is published in the GitHub repository. The legal documentation is the Privacy Policy and Legal Notice.

Two manuals complete the set. A developer manual covers system requirements, local environment setup (Apache, MySQL, Python 3 and PHP via Homebrew), creating the database from the provided schema, configuring the OpenAI API key, the project structure, the four-step pipeline in detail, the database schema and the reasoning behind key design decisions, and how to run the crawler in both single-URL and sitemap bulk modes. And a user manual, much simpler and aimed at non-technical agents, covers how to register and log in, how to submit a listing URL and understand the results, what the duplicate score means, what the AI confidence badges indicate, and the known limitations of the system, particularly that "first seen" refers to when the crawler first encountered the listing, not when it was originally published.

That last point is the single most important line in the user manual. A user who reads "first seen" as the genuine publication date will draw a wrong conclusion from a correctly functioning system, so the documentation has to carry a limitation the software cannot yet remove.

------------------------------------------------------------------------

# 5. Monitoring and control

The fourth learning outcome consists of **defining procedures for monitoring and controlling project execution, justifying the variables and instruments used**.

A professional project does not end with an initial plan. During its development we must check whether the work is progressing correctly, log problems, manage changes and evaluate the results.

**ANSWER**

This section describes how progress and quality were actually monitored during the project. It is deliberately honest about the difference between what a professional process would look like and what a single student developer working on and off over two school years actually did. Where the real process was informal, I say so and set out what the formal equivalent would be, rather than describing a procedure I did not follow.

## 5.1. Procedure for evaluating the activities carried out

Explain how you will verify that each task or phase has finished correctly.

You can establish, for example:

- acceptance criteria;
- requirements review;
- functional testing;
- code review;
- deliverable verification;
- functionality demonstration;
- checklist.

For each important task there should be some objective way of deciding whether it is truly finished.

**ANSWER**

The general rule is that a task was considered finished when the component it produced worked in isolation and then continued to work as part of the full pipeline. This two-stage check was applied after every function was implemented, and it is the single procedure that kept a fragile system stable across five crawler versions and three parser versions.

The acceptance criteria differ by component. The database schema is finished when all tables create cleanly and the pipeline can write to every one of them. The crawler is finished when, given a URL, it fetches the page, stores the HTML and returns a usable raw page id, for both the permitted real site and the demo portal. The parser is finished when, given a stored raw page, it produces a listings row with the expected fields correctly populated. The SQL scoring is finished when, given a seeded set of known duplicates, it returns the expected candidates with the expected scores, and the AI comparison when it correctly identifies a rewritten description as the same property. The full pipeline is finished when a single URL submission runs all four steps and displays a result within the time limit. For the front-end, every page must render, forms must behave, the EN/ES toggle must work, and protected pages must reject unauthenticated access, and for the admin panel, create, read, update and delete must all work and the raw viewer and live crawler log must display correctly.

The demo portal exists precisely so that "did the matching work?" has an objective answer. Five fake listing pages with known relationships mean there is an expected result to compare against, instead of a subjective judgement about whether two properties look similar. This is the difference between testing and hoping.

The two fixed deadlines also functioned as formal acceptance points. The 27 February presentation required a working prototype demonstrated live rather than described in slides, which is a considerably harder test than a written status report. The 25 March delivery required the finished first version. Each forced the project into a demonstrable state at a known date. In the second year there is no fixed deadline of that kind apart from this report, so I decide when an update is ready by applying the same acceptance rule as before: the change must work on its own and then in the full pipeline.

As an honest limitation, these criteria were applied consistently, but they were held in a notebook and in my head rather than written as a formal signed-off acceptance document. On a team project, or one with a client, they would need to be written down per task and agreed in advance.

## 5.2. Quality indicators

Define indicators that allow evaluation of the project's quality.

Try to make them specific and verifiable. For example:

- percentage of requirements implemented;
- number of tests passed;
- open and closed errors;
- response times;
- compatibility with the defined environments;
- compliance with accessibility criteria;
- documentation coverage;
- incidents detected by users;
- compliance with code conventions.

It is not necessary to use all of these indicators. Choose the ones that make sense for your project and justify them.

**ANSWER**

The indicators I chose, with their targets and actual results, are the following. For requirements implemented, the target was all functional requirements in 2.6 and all 14 were delivered. For objectives achieved, the target was all objectives in 3.4 verifiable on the delivered system, and all 12 are verifiable. The pipeline success rate target was that a submitted URL completes all four steps and returns a result, which was achieved for the permitted real site and all five demo portal pages. For duplicate detection accuracy the target was that known seeded duplicates are found and unrelated listings are not returned, and this was verified against the demo portal's known relationships. The response time target was a full pipeline within the 120-second request limit, and it stays within it.

Token usage per run was to be as low as possible without losing accuracy, and it was reduced from 15,000–20,000 to 2,000–5,000. The AI cost per comparison had to be low enough to be commercially viable, and it is about $0.0003 per call, around 0.25 € across the whole project. The stored HTML per page was to be kept bounded, and it stands at 20,000–25,000 characters. Both fixed deadlines were met, the presentation on 27 February and the delivery on 25 March. For version control coverage, every meaningful change was to be committed, and the full history is in the public repository. Security conventions, namely parameterised queries, hashed passwords and an auth check on every protected page, were applied throughout. The internationalisation target of at least two languages was met with English and Spanish. For documentation coverage, every file and folder is documented in a full automated report with per-component summaries. Finally, the code conventions of consistent naming, clear structure and commented code were applied, with commit message quality as the known weak point.

Two of these indicators deserve emphasis because they are specific to this project rather than generic. Token usage per run is a quality indicator here in a way it would not be for an ordinary web application. An accurate system that costs too much per query is not a sellable product, so reducing consumption from 15,000–20,000 to 2,000–5,000 tokens is a quality improvement rather than merely an optimisation, and it is what moves the product from technically working to commercially plausible.

Detection accuracy against known duplicates is the indicator that actually measures whether the product does its job, and it is only measurable at all because the demo portal provides a ground truth to compare against. Without that, every other indicator could be green while the core feature quietly failed.

Some indicators were deliberately not used. Accessibility compliance was not formally measured. Incidents detected by users were not measurable, because there were no real users (see 5.6). Automated test coverage was not measured, because testing was manual rather than through a test suite, which was a reasonable choice at this scale but one that would not survive the project growing.

## 5.3. Logging and evaluation of incidents

Errors, blockers, delays and unforeseen changes will appear during development.

Define how you are going to log incidents.

A simple log could include:

- date;
- description;
- affected task;
- severity or priority;
- cause;
- person responsible;
- status;
- solution applied;
- resolution date.

The goal is for incidents not to be resolved informally without leaving a record of what happened.

**ANSWER**

Incidents were recorded informally, through two mechanisms. The physical notebook held problems, ideas and what needed doing next, and was especially useful in the early stages. The Git history is the more durable record: every meaningful change was committed with a message, so a problem and the change that resolved it are both traceable, and a broken change can be rolled back. There was no separate incident register, and no tool such as Trello or a bug tracker was used. With one developer, clear deadlines and regular commits, this was sufficient in practice.

Honestly, this works for a solo project but would not survive a team or a live product. Nothing records why a change was made if the commit message is thin, and I have already acknowledged that my commit messages are not always as descriptive as they should be. That is precisely the weakness a proper incident log exists to cover.

In a professional setting, or if this project continued with real users, incidents would be recorded in a register. Each entry would have a unique ID, the date detected, a description of what was observed including the input that triggered it, and the affected component (crawler, parser, scoring, AI comparison, front-end, admin or database). It would also record the severity (blocker, major, minor or cosmetic), the root cause once identified, who is responsible, the status (open, in progress, resolved or closed), the solution applied with the commit reference, and the resolution date.

Applying that format retrospectively, the significant incidents of this project would have been logged as follows. Field extraction was unreliable across differing page structures, a major incident in the parser caused by the fact that rule-based parsing cannot generalise over arbitrary HTML, and it was solved by moving extraction to the OpenAI API. The crawler failed or returned unusable content on new sites, a major incident caused by different structures, differing amounts of raw text and inconsistent JSON-LD, solved through five successive crawler versions. The cost per run was far too high at 15,000–20,000 tokens, a major incident in the parser and comparator caused by oversized prompts and unfiltered input, solved by prompt optimisation that reduced it to 2,000–5,000 tokens. Finally, the schema did not support the comparison logic as first designed, a major database incident caused by the first schema being designed before the shape of real extracted data was known, solved with a second and final schema version.

Reconstructing these after the fact is possible but lossy, which is itself the argument for keeping the log as you go.

## 5.4. Procedure for resolving incidents

Explain what process you will follow from the moment an incident is detected until it is considered resolved.

For example:

1. log the incident;
2. classify its priority;
3. reproduce and analyze the problem;
4. propose a solution;
5. apply the change;
6. carry out tests;
7. document the result;
8. close the incident.

Adapt the procedure to your project.

**ANSWER**

The process I actually followed, and the one I would formalise, starts with detecting and recording the problem. It is noted in the notebook together with the input that triggered it, so it can be reproduced later rather than recalled vaguely. Next the priority is classified: anything breaking the pipeline end to end is a blocker and is fixed before new work continues, while anything cosmetic, or affecting only the interface, waits until the current component is stable.

The problem is then reproduced in isolation, running the affected component on its own, for example the crawler against a single URL, the parser against a single stored page, or the scorer against a single listing id. Every script in the pipeline accepts an id argument and can be run independently from the command line, which exists specifically to make this step cheap. Where the problem concerns matching rather than mechanics, it is reproduced against the demo portal, where the expected result is already known.

After that comes analysing the cause, distinguishing a fault in my own code from a change in a third party, such as a site altering its HTML or the model returning a differently shaped response. These require completely different responses, and misdiagnosing one as the other wastes a lot of time. I then propose and apply a change, keeping it scoped to the component that is actually broken. The change is tested in isolation and then integrated, which is the standing rule of the project: verify the component alone, then verify the full pipeline still runs end to end, since a fix that repairs the parser but breaks the scorer is not a fix. The change is committed with a message, which is what makes the resolution traceable and reversible, and the incident is closed only once the full pipeline has been re-run successfully.

Two points are specific to this project. Reproducing in isolation is only cheap because the pipeline was deliberately built as separate command-line scripts rather than as one monolithic process, a design decision that paid for itself repeatedly during debugging. And analysing the cause matters more here than in a typical project, because two of the most likely failure causes (a site changing its HTML, the API changing behaviour) are entirely outside my control, and the correct response to those is to adapt rather than to hunt for a bug that does not exist.

## 5.5. Management and logging of changes to resources and tasks

The initial planning may change.

It may be necessary to change a technology, expand or reduce a task, change dates, replace a service or redistribute resources.

Define how you will log these changes in order to be able to compare the initial plan with what actually happened.

For each change you can note:

- date;
- element modified;
- initial situation;
- modification made;
- reason;
- impact on time, cost or scope;
- decision adopted.

**ANSWER**

The Git history is the change record. Every meaningful change was committed, so the initial plan and what actually happened can be compared by reading the repository. The notebook held the reasoning behind the larger decisions before they were made.

Several significant changes occurred. During weeks 1–7 the parser was originally meant to parse listing fields in code without AI, but this proved at least very complicated and unreliable across differing page structures, so it moved to the OpenAI API (GPT-4.1-mini). That introduced a dependency on an external paid API but made the feature actually work, and I accepted it because it was the only approach that worked effectively. In the same weeks the crawler went through five successive versions before one was reliable, because different sites have different structures, different amounts of raw text and different JSON-LD implementations. It was the largest single consumer of effort in the project, and the change was accepted because nothing downstream works without it. The parser also went through two to three major iterations to improve extraction quality, at a time cost that was accepted.

Mid-project, the database schema changed from the first schema to a second and final version, because the first structure did not fully support the comparison logic once real extracted data existed. This meant reworking dependent queries, and I accepted it because it was cheaper to fix early than to work around indefinitely. Also mid-project, the test data plan changed from crawling real sites only to building a demo portal of five fake listing pages, because only one real site could legally be crawled and one site cannot produce varied near-duplicates. This added a small build task and made the matching logic testable at all, so it unblocked all downstream testing.

On 6 March 2026 ChromaDB, which had been planned as a vector database for semantic comparison, was removed entirely and the comparison script deleted. GPT-4.1-mini handled description comparison more accurately and with far less complexity, and it already worked. The change removed a whole technology from the stack, simplified deployment and left the vector_matches table unused, and it was accepted because the accuracy and simplicity gain outweighed keeping it.

After 27 February the AI prompts, which used 15,000–20,000 tokens per run, were optimised to 2,000–5,000 tokens, because the cost per run was far too high to be commercially plausible. This was roughly a fourfold cost reduction, accepted because cost is a product requirement, not just an optimisation. The front-end, a basic functional interface for the presentation, was also upgraded and cleaned across several iterations, because the presentation showed a working prototype while the delivery needed a finished product, with the time cost concentrated in the final phase. Throughout the project, image and location matching, which were considered in the original concept, were left out because their complexity and cost were disproportionate to the benefit over text and numeric comparison. This narrowed the scope and protected the deadline, and it is documented as a future extension.

The pattern is consistent and worth stating explicitly: almost every change simplified the system rather than extending it. Dropping ChromaDB, excluding image matching, and consolidating extraction into a single AI call all reduced scope in exchange for something that worked reliably within the available time. The one change that went the other way, adopting the OpenAI API, added an external dependency, and it is recorded as the project's single largest risk in 4.5 for exactly that reason.

## 5.6. User participation in the evaluation

When possible, involve real or representative users in the evaluation.

You can use:

- user testing;
- questionnaires;
- interviews;
- observation;
- satisfaction forms;
- usability testing;
- validation sessions.

Explain who would participate, what aspects they would evaluate and how you would record the results.

If your project does not allow testing with users, justify it and consider how it could be done in a real scenario.

**ANSWER**

No structured testing with real users was carried out. This is a genuine gap rather than something I want to dress up, and it is worth saying why it happened: the first stage ran for two and a half months against two fixed deadlines, the system needed a working pipeline before it was worth showing to anyone, and the front-end was deliberately built last, which left very little window in which there was something testable to put in front of a real agent.

Two things partially substitute for it. First, the public presentation on 27 February was a live demonstration to a real audience, run as a working demo rather than as slides. It covered the purpose of the project, the duplicate detection pipeline and the database structure, and the audience came away with a clear understanding of the project. The main thing that did not go to plan was time: the crawler section had to be cut short. That is feedback about the product's explainability, even if it is not usability testing. Second, the requirements themselves come from direct experience of the problem, since I identified this need while working in a real agency, so the product is grounded in observed user behaviour even though it was not validated with users afterwards.

In a real scenario, the evaluation would target the buyer persona identified in the market analysis: real estate back-office staff aged roughly 30 to 45 on the Costa Blanca, meaning the administrators who create the listings rather than the agents who do the selling, since they are the people who deal with duplicates daily and would recognise the value immediately.

I would use several methods. A think-aloud usability test with 5–8 back-office administrators would show whether they can submit a URL and correctly interpret the result without being taught. An accuracy validation with 2–3 agencies, using their own live listings, would show whether the system finds the duplicates they already know about and whether it produces false positives. The same participants would then take part in a semi-structured interview after use, to find out whether it saves real time and whether they would pay 30 € a month for it. A satisfaction questionnaire for all participants would cover the clarity of results, confidence in the score and perceived usefulness, and a comprehension check would test whether they understand what the score means and that "first seen" is not the true publication date. Results would be recorded per participant per task, covering completion, time taken, errors and verbatim comments, with the accuracy test scored against each agency's own ground truth.

The comprehension check matters most of all. If users read "first seen" as the genuine publication date, the product actively misleads them even while working exactly as designed. That is the kind of failure no amount of technical testing detects, and it is the strongest argument for doing this properly before the tool reaches a real client.

## 5.7. Compliance with the specifications document, when one exists

If the project has a specifications document, contract, requirements document or formal specification, you must define how you will verify compliance with it.

You can use a traceability matrix that relates each requirement to:

- the functionality that implements it;
- the task in which it is developed;
- the test that verifies it;
- its final status.

If your project does not have a formal specifications document, state so. You may use your own requirements and scope specification as an equivalent reference.

**ANSWER**

There is no formal specifications document. FirstListing has no client, no contract and no external pliego de condiciones. It is a self-directed project whose requirements I defined myself, so there is no third-party specification to verify compliance against.

In its place I use my own requirements and scope definition, meaning the functional and non-functional requirements in 2.6 and the objectives in 3.4. These serve the same function: a fixed, written statement of what the system must do, set down before the work and checked against afterwards.

To trace each requirement, I related it to the file that implements it, the phase in which it was developed, and the test that verifies it, and all of them are complete. Registration and login (RF-01) is implemented by register.php and login.php in phase 9, and is verified by registering, logging in and confirming the stored password is a hash. The three roles (RF-02) are a role ENUM on the users table, developed in phases 1 and 9 and verified by inspecting the schema and creating a user of each role. The protected areas (RF-03) rely on a session check at the top of each protected page, in phase 9, verified by requesting a protected page while logged out and confirming the redirect. URL submission (RF-04) is the dashboard form, also phase 9, verified by submitting a URL.

The crawl and store step (RF-05) is implemented by python/crawler_v4.py in phase 3 and verified by confirming a new raw page row containing the HTML. The AI field extraction (RF-06) is scripts/openai_parse_raw_pages.php in phase 4, verified by a listings row with the expected fields. The weighted SQL scoring (RF-07) is scripts/find_duplicates.php in phase 6, verified by running it against seeded duplicates and comparing with expected scores, and the AI description comparison (RF-08) is scripts/ai_compare_descriptions.php, also phase 6, verified by rewriting a description and confirming it is still matched. The ranked results (RF-09) are the dashboard results table in phase 9, verified by submitting a URL and reading the table. Search tracking (RF-10) uses the search_usage table, developed in phases 1 and 9 and verified by running two searches and confirming the monthly counter increments.

The admin panel (RF-11), made up of the admin dashboard, AI listings viewer, raw viewer and crawler log, was built in phase 9 and verified by performing each CRUD operation and viewing the live log. The EN/ES interface (RF-12) is public/js/lang.js, verified by toggling the language on each page, and the AI chat widget (RF-13) is public/chat.php and the chat widget partial, verified by asking the widget a question about the project. The legal pages (RF-14), public/privacy.php and public/legal.php, were produced in phase 11 and verified by opening both pages and checking the required content.

The non-functional requirements are traced in the same way. Security is covered by PDO parameterised queries, bcrypt and session authentication throughout the project, verified by reviewing query construction and password storage. Performance is covered by the 120-second limit and the cap of the AI comparison at the top 5 candidates, developed in phase 10 and verified by timing a full run and confirming the cap in the dashboard code. Cost is tokens per run, measured at 2,000–5,000 in phase 10. Data protection is the minimum data collected and the documented processing, from phase 11, verified by reviewing the users table and the Privacy Policy.

Every stated requirement is implemented and verifiable. What this does not claim is that the product is complete as a commercial proposition: the exclusions listed in 3.4, and particularly the absence of true publication dates, are deliberate limitations of scope rather than unmet requirements.

------------------------------------------------------------------------

# 6. Conclusions

Close the report with a summary of the project carried out.

The conclusions should answer, at least, these questions:

- has the initially identified need been resolved?;
- have the objectives been achieved?;
- what final result was obtained?;
- what were the main difficulties?;
- what have you learned?;
- what improvements or extensions could be made in the future?

The conclusions must be related to what you have actually developed and to the objectives established at the beginning of the report.

**ANSWER**

Has the initially identified need been resolved? Partly, and it is worth being precise about which part. The need had two components: identifying that several advertisements describe the same property, and determining which one came first. The first is solved, because the system reliably groups duplicate listings, including cases where the description has been rewritten, which was the hardest version of the problem. The second is not fully solved, because the system orders by when the crawler first encountered a listing rather than by genuine publication date, and the portals do not reliably expose the latter. The tool therefore delivers real value today as a duplicate detector, and the "which came first" claim is currently limited by data availability rather than by the implementation.

Have the objectives been achieved? Yes. All twelve objectives set out in 3.4 are implemented and verifiable on the delivered system, and both fixed deadlines of the first stage were met: a working prototype demonstrated live at the presentation on 27 February 2026, and the first version delivered on 25 March 2026. Since then, during the second year, I have kept smoothing out and streamlining the project, and more updates are planned.

The final result is a working web application with a four-step pipeline from URL to ranked duplicate list, three user roles, a user dashboard, an administration panel with full CRUD and a live crawler log, a bilingual interface, an AI chat widget, GDPR and LSSI legal pages, and a documented public repository. It runs on a local stack and is designed to be deployable to a normal server environment.

Three difficulties stand out. The crawler was the largest single consumer of effort, with five versions before one worked reliably, because different sites have different structures, different amounts of raw text and different JSON-LD implementations. The parser was the hardest design problem: extracting structured fields from arbitrary HTML in pure code proved at least very complicated, and only worked properly once I moved to the OpenAI API. And cost control turned out to be a real engineering constraint rather than an afterthought, because at 15,000–20,000 tokens per run the system worked but would not have been sellable, and reducing that to 2,000–5,000 took deliberate effort.

Technically, I learned how to integrate a language model into a pipeline as an ordinary component and, more usefully, when not to use it. The two-stage design, filtering cheaply in SQL before spending anything on AI, is the most transferable idea in the project. I also learned that removing technology is often the right decision rather than a retreat, since dropping ChromaDB simplified the system and improved accuracy at the same time.

On process, I learned that building the front-end last was correct, and that testing every function in isolation and then integrated is what kept a fragile system stable across nine major component rewrites. The clearest weakness I would fix is my commit messages, which are not descriptive enough to serve as the change record I have relied on them being.

As for future improvements, in rough order of value, the first would be genuine publication dates, which would convert the product from a duplicate detector into what it actually claims to be and is the single highest-value extension. Next would come more portals, which is the main scaling problem and the main source of fragility, followed by image-based matching, deliberately excluded here, which would catch listings whose text has been completely rewritten. A real deployment would need a production server with firewall, automated backups and monitoring, and a billing integration would be needed because the subscriptions table exists but no payment flow was built. Last, validation with real users is the largest gap in the evaluation, and the first thing I would do if the project continued.

Overall, the project set out to demonstrate everything learned during the course by building something well beyond a simple example, and it does: server-side programming in two languages, persistent storage, a full CRUD administration panel, client-side work, internationalisation, an external API integration, legal compliance and version control, all applied to a real problem I encountered in a real job. It is a working product with honest, documented limitations rather than a finished commercial platform. It is also still being improved in the second year, and more updates are planned, so I see the current state as a solid base and not as the end of the project.

------------------------------------------------------------------------

# 7. Bibliography and sources consulted

Include the sources you have actually used during the project: technical documentation, books, articles, regulations, manuals, documentation for languages, frameworks, APIs, libraries or services.

Use a consistent format across all references.

Do not include sources that have not been consulted, and distinguish, where applicable, between bibliography, technical documentation and regulations.

**ANSWER**

The technical documentation consulted was the following. The OpenAI API documentation (models, chat completions and token usage) was consulted throughout parser, comparator and chat widget development. The PHP manual from the PHP Group was used in particular for PDO, sessions and password hashing, and the Python 3 documentation from the Python Software Foundation for the standard library, HTTP requests and HTML parsing. The MySQL / MariaDB reference manual, from Oracle and the MariaDB Foundation, covered schema definition, joins and conditional expressions, and the Apache HTTP Server documentation from the Apache Software Foundation covered local configuration via Homebrew. I also used the Git and GitHub documentation for version control and repository hosting, and Schema.org for the JSON-LD structured data vocabulary, consulted while designing the crawler's extraction logic.

The regulations consulted were Regulation (EU) 2016/679, the General Data Protection Regulation (GDPR), which I reviewed to confirm the application's data handling is compliant and whose Art. 6.1.b is used as the legal basis for processing, and Ley 34/2002, de Servicios de la Sociedad de la Información y de Comercio Electrónico (LSSI-CE), the basis for the Legal Notice published on the site.

The project sources are the FirstListing project repository by Sorensen, O. (https://github.com/oscarsorensen/FirstListing), and Sorensen, O., Proyecto Intermodular I, the first-year project report on FirstListing, which is the source of the sector analysis, business figures and development timeline reused in this document.

The sector sources are my direct professional experience in a real estate agency on the Costa Blanca, which is the origin of the problem statement and the source of the estimate of 850–900 active agencies in Costa Blanca South, and the portals Idealista and Fotocasa, examined as the closest comparable products in the sector.

> **This bibliography needs completing before submission — see item 5 in the manual review list.** The entries above reflect what was genuinely consulted, but most lack specific URLs, version numbers and access dates, and the sector figures come from personal experience rather than a citable published source.

------------------------------------------------------------------------

# Final checklist

Before submitting the report, check that:

- you have analyzed real companies and needs in the sector;
- you have justified the type of project and its requirements;
- you have defined objectives and scope;
- you have carried out a feasibility study;
- you have divided the project into phases and tasks;
- you have established a time plan;
- you have identified material and human resources;
- you have carried out an economic assessment;
- you have considered risks, permits and documentation;
- you have defined execution procedures;
- you have established quality indicators;
- you have planned how to log incidents and changes;
- you have explained how you will evaluate the project and, where applicable, how users will participate;
- you have documented the technologies used;
- you have carried out a self-assessment and coherent conclusions;
- the report describes your specific project and does not contain generic sections unrelated to it.


------------------------------------------------------------------------

# Manual review list — for me to complete before submitting

Everything above was written from my *Proyecto Intermodular I* report and from the FirstListing codebase itself. The points below are either missing from that source material, inconsistent between sources, or claims I should confirm before handing this in.

## A. Content gaps — I need to write or research these

| # | Section | What is needed |
|---|---|---|
| 1 | **2.8 Aid and grants** | **The biggest gap.** I never researched this. I need to check national, Comunitat Valenciana and local programmes for digitalisation, entrepreneurship and innovation, then either name a specific programme FirstListing could apply to or state with reasoning why none fits. The current text is honest but thin. |
| 2 | **2.1 Companies in the sector** | I only named two comparable companies (Idealista and Fotocasa) and gave no figures for their size, revenue or technologies. If real sector research is expected, this needs concrete data and possibly one or two more companies. |
| 3 | **2.7 Tax and labour obligations** | Kept deliberately general. If specifics are expected I need to decide and state the legal form (autónomo vs. SL) and name the actual fiscal obligations — IVA, IRPF, RETA, quarterly filings. I should not guess at these. |
| 4 | **2.7 and 4.5 Occupational risk prevention** | The PRL section of my Intermodular report was never written — it was still placeholder text. I have written the risks from the section that *was* completed. If a reference to Ley 31/1995 or a formal written risk assessment is expected, I need to add it. |
| 5 | **7. Bibliography** | The entries are real but lack URLs, versions and access dates. I need to add them and apply one consistent citation format. |
| 6 | **Figures and annexes** | My Intermodular report had interface screenshots, a logo and a directory tree in annexes; this report has none. I should decide whether to add an annex with interface screenshots, an entity-relationship diagram of the six tables, and a Gantt chart for section 3.3. |

## B. Inconsistencies I found and need to resolve

| # | Where | Issue |
|---|---|---|
| 7 | **Duplicate score threshold — real code bug** | `scripts/find_duplicates.php` has `HAVING match_score >= 5` in the SQL, but the comment on line 46 of that same file says `HAVING >= 10`, and the message at `public/user.php:390` tells the user "No candidates scored 10 or higher." The code and both descriptions disagree with each other. I have deliberately written "above the cut-off" in this report rather than commit to a number. **I need to decide whether the threshold is 5 or 10, then fix the SQL, the comment and the user-facing text so all three agree — and then state the number in 3.1.** |
| 8 | **Timeline arithmetic** | My Intermodular report says "Week 1–7", then "Week 7–10", and "roughly two and a half months" — but it also says 20 February was three weeks in (so a start around 30 January) and that delivery was 25 March, which is about eight weeks, not ten. I need to reconcile the week numbers with the actual dates in 3.3 and 4.6. |
| 9 | **"Idealita"** | My Intermodular report says "Idealita". I have written **Idealista** throughout, assuming that was the intended company. Confirm. |
| 10 | **`vector_matches` table** | Still present in the schema but left over from the ChromaDB plan, which was removed on 2026-03-06. I describe it as unused in 3.1. I should either drop it from the schema or keep the explanation. |
| 11 | **Search quota** | `search_usage` tracks searches per user per month, but I could not find an enforced limit anywhere in the code. I have therefore written this as "records searches" rather than "enforces a quota" (RF-10). Confirm that is correct, or implement the limit. |

## C. Claims to verify before submitting

| # | Claim | Why it needs checking |
|---|---|---|
| 12 | Project dates — start ≈ 30 Jan 2026, presentation 27 Feb 2026, delivery 25 Mar 2026 | Taken from the Intermodular report. Confirm the year and the start date. |
| 13 | PHP version stated as "PHP" generally in 3.6 | Confirm the version actually running if a specific number is wanted. |
| 14 | 850–900 agencies on Costa Blanca South | My own estimate from working in the sector. Fine to present as such, but if a citable source is expected I need one. |
| 15 | All financial figures — 24,000 € salary, ~7,500 € materials, ~31,500 € total, 88 break-even clients | Carried over unchanged from the Intermodular report for consistency. Confirm I still stand behind them. |
| 16 | "All 14 functional requirements delivered" (5.2) and the full traceability matrix (5.7) | **I invented the RF-01…RF-14 requirement IDs for this report** — they did not exist before. Walk through the list once against the running application and confirm every row is genuinely complete before claiming it in writing. |
| 17 | Developer and user manuals (4.8) | The Intermodular report described these as planned. Confirm whether they now exist; if not, either write them or change the wording from a description to future work. |

## D. Decisions only I can make

| # | Question |
|---|---|
| 18 | **Is this DAW final project the same FirstListing project, or a continuation of it with new work?** I have written the entire report as FirstListing, retrospectively, using first-year dates. If the second-year project is meant to extend it, then 3.3, 3.4, 4.1, 4.6 and 6 need new scope, new dates and new objectives. **This is the most important question on the list — everything else is detail by comparison.** |
| 19 | **Tense and framing.** The template is written as though the project is about to start; I have written retrospectively because it is finished. Confirm the assessor accepts that, since it affects almost every section. |
| 20 | **Whether to delete the guidance text**, the `-- PUT THE ANSWER ... HERE` markers and the `**ANSWER**` labels before submitting. I left all of it in place as asked. |
| 21 | **Whether the report should be in Spanish.** I have written in English to match the source material and the `.EN` filename, but the module and the original template are in Spanish. |