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

**The portals.** After researching the sector — drawing on both my own experience in it and a considerable amount of recent research — I have not found a company that does exactly what FirstListing does. The closest references are **Idealista** and **Fotocasa**.

- *Main activity:* operating online real estate listing portals.
- *Products and services:* a search engine over property advertisements, and the publication channel that agencies and private sellers use to reach the market.
- *Target audience:* double-sided — consumers searching for property on one side, and agencies paying to publish on the other.
- *Scope:* national.
- *Business model:* paid publication and subscription plans for agencies, supported by advertising.

What matters most for this project is what they do **not** do. Neither shows which advertisement was published first, and neither groups near-identical advertisements together so the original can be found. They make listings visible; they do not make duplication visible. That gap is exactly the space FirstListing occupies, and the fact that the two largest players in the sector have not closed it is the strongest evidence I have that the idea is genuinely new rather than simply unbuilt.

**The agencies.** These are the companies FirstListing would actually sell to, so they deserve as much attention as the portals.

- *Main activity:* sale and rental of residential property.
- *Products and services:* a brokerage service rather than a product.
- *Target audience:* on Costa Blanca South, largely foreign buyers.
- *Size and scope:* small, usually a handful of employees per office, operating locally. There are approximately 850–900 active agencies in that area alone.
- *Business model:* commission on completed transactions.
- *Relevant characteristic:* how they publish. The same property reaches the market through several portals at once, through XML feeds, and through co-brokering agreements between agencies.

That last point is the important one. The distribution model these companies use is precisely what generates the duplicates, which means the potential customers and the cause of the problem are the same group of companies. That is unusual, and it is what makes the product a straightforward sell: they are not being asked to fix someone else's mess, they are being offered a way to see through their own.

**Where FirstListing sits.** Between the two groups — not a portal and not an agency, but a B2B software supplier selling a subscription tool to the second group, addressing a problem the first group has chosen not to solve.

## 2.2. Typical companies: organizational structure and functions of the departments

Choose one or several representative companies in the sector and explain how a company of that type could be organized.

You must identify its main departments or areas and explain what function each one performs.

In a software development company there could be, for example, areas of management, analysis, development, design, systems, quality, support, sales or administration. Not all companies will necessarily have the same structure.

The important thing is to relate the organization described to the reality of your project's sector.

-- PUT THE ANSWER for 2.2 HERE

**ANSWER**

The most useful company to describe here is FirstListing itself, because it is the type of company this project would create, and because it can be contrasted with how a larger software company in the same sector would be organised.

**Realistic version — micro-enterprise.** FirstListing is designed as a small technology startup building a web-based system for analysing and clustering real estate listings. In its realistic form it operates as a micro-enterprise with a single founder. The founder acts as both CEO and technical lead, covering strategy, programming, database design, data processing and client communication. The structure is completely flat, which means decisions are fast and organisational complexity is close to zero.

Development is the core department from day one, because the company is fundamentally a software company. In the initial stage the founder also handles product design, basic marketing and basic accounting internally, while **tax management and legal advice are outsourced from the beginning** to guarantee compliance and reduce risk. That split is deliberate: the areas where a mistake is cheap are kept in-house, and the areas where a mistake is expensive are given to someone qualified.

**Hypothetical scaled version — 5 to 7 people.** If the company grew, it would divide into functional areas: leadership (CEO / Product Owner), backend development, frontend development, data engineering, and infrastructure management.

**Functions of each area:**

| Area | Function |
|---|---|
| Leadership / Product | Sets direction, prioritises features, owns the client relationship |
| Development | Builds and maintains the software, manages the database, keeps the platform working correctly |
| Data engineering | Owns the crawler and parser — the components that acquire and normalise listing data |
| Infrastructure | Hosting, deployment, backups, monitoring |
| Marketing | Studies market needs, communicates the value of the product, attracts clients |
| Administration | Billing, payments, basic accounting |
| Tax and legal | Outsourced; ensures fiscal and legal compliance |
| Support | Handles client incidents; becomes a separate role only once client numbers justify it |

**Growth over time.** During the first year the structure stays lean, with most advanced services subcontracted. Within five years, if the company grows, marketing and customer management could become internal roles, and development could split into separate backend and frontend positions. In ten years all key departments — development, marketing and administration — could be fully internalised.

The principle underneath all of this is that the company size must match the scope and complexity of the product, not the other way round. A duplicate-detection tool sold to local agencies does not need a department structure on day one, and inventing one would be a cost rather than an advantage.

## 2.3. Needs most demanded from companies

Based on the previous research, identify which needs seem to be most frequently demanded by customers or users in the sector.

Think about real problems that can be solved through software: process automation, information management, communication, e-commerce, education, data analysis, mobility, security, entertainment, accessibility, etc.

It is not enough to state that a need exists. Whenever possible, justify why you consider it relevant based on your analysis of the sector.

**ANSWER**

The clearest and most frequently demanded need in this sector is **knowing whether a listing is the original or a copy**.

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

**Affordable AI.** The single biggest enabler is that language models have become cheap enough to use as an ordinary component rather than as a research project. The description comparison in FirstListing costs roughly $0.0003 per call using GPT-4.1-mini, and the entire project, across all development and testing, cost about 25 cents in API usage. Deciding whether two rewritten property descriptions describe the same flat is very hard to solve with string matching and straightforward for a language model. That capability existing at that price is what makes the product possible at all.

**A disorganised market.** The Spanish real estate market is extraordinarily disorganised. That is unhelpful for the people working in it, but it is precisely what creates the opportunity — a tidier market with a single authoritative listing registry would not need this tool.

**Digital transformation of small agencies.** Agencies are gradually adopting more software, which lowers the barrier to selling them another subscription tool, particularly to the younger back-office staff who are already comfortable with web applications.

**Concrete sizing.** The opportunity can be quantified rather than asserted. At 30 € per month, one agency pays 360 € per year. In the target market of 850–900 agencies on Costa Blanca South:

| Adoption | Agencies | Annual revenue |
|---|---|---|
| 10 % | ~90 | ~32,400 € |
| 25 % | ~225 | ~81,000 € |

These are modest, specialised B2B numbers rather than mass-market ones. A realistic market share for FirstListing is small, because it is a specialised tool rather than a consumer product. But even low adoption produces meaningful revenue, and duplicate detection on its own already provides clear value, which is what makes the project worth continuing in its current direction.

**Expansion.** At national level the addressable market is only a fraction of all Spanish agencies, but it still represents several thousand potential professional users. Internationally the same problem exists, but expansion would have to happen country by country, because each market has different portals and a different structure. Any future change of direction is therefore more likely to be an extension of the concept than a complete pivot.

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

The appropriate solution is a **client–server web application**.

Working through the questions that determine this:

- **Does it need to work from a browser?** Yes. The users are agents and back-office administrators who already spend the day in a browser, moving between portals and their own systems. The tool has to sit where the work already happens.
- **Does it require installation?** No, and it must not. Asking a small agency to install and maintain desktop software would be a serious adoption barrier for an audience that is only moderately tech-savvy.
- **Must it work on several devices?** It has to be usable from any office computer regardless of operating system. A browser-based application gives that for free.
- **Does it need to store data?** Yes, and heavily. The entire premise is comparing a new listing against everything seen before, which is impossible without persistent storage. Roughly 95 % of this project is database logic — the database is not a supporting feature, it is the product.
- **Will there be different types of user?** Yes. Three roles are implemented: `agent`, `private` and `admin`.
- **Does it need a server?** Yes. Crawling and AI calls must run server-side: the API key cannot be exposed to the client, and browsers cannot crawl third-party sites because of cross-origin restrictions.
- **Must it integrate with other systems?** Yes — with the OpenAI API, and with the third-party websites it reads.

**Why not the alternatives.** A mobile application was rejected because this is desk work done on a computer, not on the move, and it would add app-store distribution overhead for no benefit. A desktop application was rejected because of installation, updates and cross-platform cost. A pure API or web service was rejected because the target users are not developers and need a visual interface. A management system in the classic sense does not fit either, because the value here is in the analysis rather than in record-keeping.

The chosen architecture is therefore a web application with a server-side processing pipeline, an administration panel, and a browser front-end — which is also, conveniently, the architecture that best demonstrates the range of skills the course covers.

## 2.6. Specific characteristics of the project according to the requirements

Transform the needs identified into specific characteristics of the project.

For example, if users need to access it from any device, one characteristic could be having an adaptable web interface. If they need to retain information, the project must incorporate data persistence. If there are different profiles, it will be necessary to implement authentication, authorization and permission management.

Try to differentiate between:

- functional requirements: what the system must do;
- non-functional requirements: security, performance, usability, compatibility, availability, accessibility, maintainability, etc.;
- technical or external constraints.

This section should allow understanding of what the project will have to fulfill in order to truly respond to the need identified.

**ANSWER**

**Functional requirements — what the system must do**

| # | Requirement |
|---|---|
| RF-01 | Allow user registration and login, with passwords stored hashed |
| RF-02 | Support three user roles — `agent`, `private`, `admin` |
| RF-03 | Restrict user and admin areas to authenticated sessions |
| RF-04 | Accept a listing URL submitted by a logged-in user |
| RF-05 | Crawl that URL and store the raw page content |
| RF-06 | Extract the structured fields from the raw page using AI: price, surface area, rooms, bathrooms, property type, listing type, address, reference and description |
| RF-07 | Score every stored listing against the submitted one using a weighted SQL comparison |
| RF-08 | Compare the descriptions of the strongest candidates using AI, to catch rewritten text |
| RF-09 | Present the candidates ranked, with their score |
| RF-10 | Record each user's searches per month |
| RF-11 | Provide an admin panel with CRUD over the stored data, a raw-crawl viewer and a live crawler log |
| RF-12 | Provide the interface in English and Spanish via a toggle |
| RF-13 | Provide an AI chat widget that answers questions about the project |
| RF-14 | Publish a Privacy Policy and a Legal Notice |

**Non-functional requirements**

- **Security.** All database access through PDO with parameterised queries; passwords hashed with bcrypt; session-based authentication with an access check at the top of every protected page.
- **Performance and cost.** The pipeline runs synchronously while the user waits, so it must complete within a reasonable time — a 120-second limit is set for the request. Cost per run must stay low, which is why the expensive AI description comparison runs only on the top five SQL candidates rather than on every match.
- **Usability.** The audience is only moderately tech-savvy, so the flow must be a single input and a clear result table, with no configuration to understand before getting a first answer.
- **Compatibility.** Must work in a normal desktop browser with no plugins.
- **Internationalisation.** English and Spanish, because the target market serves foreign buyers and the staff using it are frequently not native Spanish speakers.
- **Maintainability.** Readable, commented code with a clear folder structure. This is a functional requirement in disguise: the crawler and parser are known to need ongoing adjustment, so code that is hard to change is code that will break permanently.
- **Data protection.** Collect only the minimum necessary data, and document how it is processed.

**Technical and external constraints**

- The system depends on the OpenAI API. If it becomes unavailable or changes price, the core feature is directly affected.
- Extraction depends on the structure of third-party websites, which can change without warning.
- Crawling requires permission and polite behaviour, which limits how many real sites can be used and is the reason a demo portal had to be built for testing.
- The project runs on a local development stack rather than a production server, so anything requiring a live public deployment is outside what could be demonstrated.

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

If FirstListing were run as a real business rather than as an academic project, the following obligations would apply.

**Business activity and invoicing.** The founder would have to be registered before invoicing clients, issue proper invoices for the subscription, and meet the corresponding periodic tax filings. Because the product is a recurring subscription rather than a one-off delivery, invoicing would be periodic rather than per project. In the company design described in 2.2, **tax management and legal advice are outsourced from the very beginning**, precisely so these obligations are handled correctly rather than improvised by a developer.

**Hiring of staff.** In the realistic one-person version there are no employment obligations at all. They would appear only in the scaled 5–7 person version, at which point employment contracts, social security registration and payroll become relevant.

**Occupational risk prevention.** This is a software business, but it is not risk-free. The relevant risks are electrical — a badly installed 220 V connection can cause serious injury or fire, and low-voltage devices can cause minor shocks — and, much more significantly, ergonomic. Long hours in front of a computer cause back pain, eye strain, carpal tunnel syndrome and burnout, all of which can lead to sick leave and financial loss. The preventive measures are safe cable installation, an ergonomic workspace and structured breaks. In a company with employees these would have to be formalised in a written risk assessment and prevention plan rather than left to good habits.

**Intellectual property and licences.** The project's own code is mine. The development stack — Python, PHP, MySQL, Apache — is free and open source, so no licence is purchased, but the licences still exist and their terms still apply.

**Data protection.** GDPR applies, and it is the obligation with the most direct effect on the product itself. FirstListing collects only the minimum necessary data: username, optional email, a bcrypt-hashed password, and the URLs searched. The legal basis for processing is performance of a contract (GDPR Art. 6.1.b). A Privacy Policy and a Legal Notice are published on the site covering legal bases, retention, erasure rights and third-party processors, with OpenAI identified as the sole third-party processor. The GDPR was reviewed specifically to confirm that the application's data handling is compliant.

**Terms of use of third-party services.** Two apply. The OpenAI API has its own usage terms, which the project must respect. More importantly, crawling third-party websites is governed by those sites' terms — which is why crawling permission for the primary test site was obtained directly from the site owner, and why the crawler identifies itself with a polite User-Agent and waits 1.5 seconds between requests.

**Not applicable.** No sector-specific real estate regulation applies beyond standard GDPR, because the tool is aimed at private B2B users, does not interact with public administrations, and does not handle transactions or hold client money.

## 2.8. Possible aid or grants for the incorporation of new technologies

Research whether a project of these characteristics could qualify for aid, digitalization programs, entrepreneurship, innovation or the incorporation of new technologies.

It is not essential that a specific applicable aid exists. If you do not find a suitable one, state so with reasoning.

The important thing is to demonstrate that you have considered the possibility of financing or external support.

**ANSWER**

A project of this type — a digital product created by a new micro-enterprise, incorporating artificial intelligence — is in principle exactly the kind of activity that public digitalisation, entrepreneurship and innovation programmes exist to support. A real launch should therefore begin by checking what is available at national level, at regional level in the Comunitat Valenciana, and locally, as well as any support offered through the education centre itself.

I have to be honest that I have not yet carried out that research properly, so I cannot name a specific programme and confirm that FirstListing would qualify for it.

What I can say is that the need for external funding is genuinely small. As set out in 3.7 and 4.7, the only unavoidable cash costs in the first year are hosting, a domain and the API, which together amount to a few hundred euros. The dominant cost by a wide margin is my own time. That has two consequences for this section: any aid would shorten the runway rather than decide whether the project happens at all, and the most useful form of support would be one that reduces the cost of becoming self-employed, rather than one that subsidises equipment purchases — since no equipment needs buying.

> **This section needs real research before submission — see item 1 in the manual review list at the end of this document.**

## 2.9. Work outline for developing the project

Close this first phase by explaining, in general terms, how you are going to approach the project.

This outline will be an initial view of the work that you will later develop in greater detail in the planning.

Indicate the major stages planned: analysis, design, environment setup, implementation, testing, documentation, deployment, evaluation, etc., adapting them to your project.

**ANSWER**

The honest description of how this project was approached is that I started from the ground up rather than following a strict predefined plan. That was probably not the most time-efficient approach, but it made sense given the nature of the work: roughly 95 % of the project is database logic, so that is where I started.

The major stages were:

1. **Analysis and paper design.** Sketching the database structure by hand — how the components relate to each other and what data needs to flow where.
2. **Environment setup.** Local Apache and MySQL through Homebrew, VS Code, Python 3, PHP, a Git repository pushed to GitHub, and an OpenAI API account.
3. **Core data layer.** A first SQL schema, then a second and final version once the structure was right.
4. **Acquisition — the crawler.** Five successive versions before one worked reliably.
5. **Extraction — the parser.** Two or three major iterations, ending with the OpenAI API, which was the only approach that worked effectively.
6. **Comparison logic.** The weighted SQL scoring pass, then the AI description comparison layered on top of it.
7. **Test data.** A demo portal of five fake listing pages, built because only one real site could legally be crawled.
8. **Front-end.** HTML, CSS and vanilla JavaScript, plus the admin panel — deliberately last.
9. **Refinement.** Cleaning the code, cutting token usage, and improving the interface.
10. **Documentation and delivery.** Written documentation, the GitHub repository, the public presentation and the final delivery.

Rather than moving linearly through these stages, most of the work happened in continuous iteration between stages 4, 5 and 6, which were developed against each other rather than in sequence — problems in the crawler only became visible when the parser ran against its output, and problems in the parser only became visible when the scoring ran against its output. The detailed timing is set out in 3.3 and the dependency reasoning in 4.1.

------------------------------------------------------------------------

# 3. Project design

The second learning outcome consists of **designing the project by explicitly developing the phases that make it up**.

In this part we are no longer so focused on justifying that a need exists, but rather on concretely defining the solution we intend to develop and verifying that it is viable.

**ANSWER**

This section moves from justifying the need to defining the solution. It describes the components that make up FirstListing, confirms the project was achievable with the knowledge, tools and time available, breaks the work into phases with dates, states the objectives and the explicit limits of the scope, and identifies the resources, funding, documentation and quality criteria involved.

Because the project is now built, this section is written retrospectively. It describes the design that was actually followed rather than one that was only intended, and where the design changed during development I say so rather than presenting the final state as if it had been planned from the start.

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

**Users and profiles.** Three roles are defined in the database as an ENUM, so a user can hold only one of them: `agent` (the default, and the main commercial target), `private`, and `admin`. Agents and private users reach the dashboard and run duplicate checks; admins additionally reach the administration area.

**General architecture.** A classic client–server web application with a server-side processing pipeline. The browser holds no logic beyond interface behaviour. Everything that matters — crawling, AI calls, scoring — happens on the server. That is not a stylistic choice: the API key must stay private, and a browser cannot crawl third-party sites because of cross-origin restrictions.

**Main modules.**

| Module | Language | Role |
|---|---|---|
| Crawler | Python 3 | Fetches a listing page and stores its raw HTML. Works in single-URL mode and in sitemap bulk mode |
| Parser | PHP + OpenAI | Reads a stored raw page and extracts the structured fields |
| Duplicate scorer | PHP + SQL | Scores every stored listing against the submitted one |
| Description comparator | PHP + OpenAI | Compares descriptions for the strongest candidates |
| Web application | PHP | Registration, login, dashboard, results, public pages |
| Admin panel | PHP | CRUD, AI listings viewer, raw crawl viewer, live crawler log |
| Chat widget | PHP + OpenAI + JS | Answers visitor questions about the project |

**The pipeline.** Submitting a URL triggers four steps in sequence, synchronously, while the user waits:

1. The Python crawler fetches the page and returns the id of the stored raw page.
2. The PHP parser sends that raw page to GPT-4.1-mini and writes the extracted fields to the listings table.
3. The SQL scorer compares the new listing against all others using a weighted match — reference 5 points, price 3, surface area 3, rooms 2, bathrooms 2, property type 1, listing type 1, giving a **maximum of 17 points**. Candidates above the cut-off are returned, ordered by score.
4. The top five candidates that have descriptions are sent to GPT-4.1-mini for a semantic comparison, which catches properties whose text has been rewritten but which are still the same flat.

The two-stage design is deliberate and is the main cost control in the system: the cheap, deterministic SQL pass narrows the field, and only then is the expensive AI pass applied. Reversing that order would produce the same answers at many times the cost.

**Information handled.** Six tables: `raw_pages` (crawled HTML), `ai_listings` (extracted structured fields), `users`, `subscriptions`, `search_usage` (searches per user per month), and `vector_matches`. The last of these is left over from the original plan to use a vector database for semantic comparison; that approach was dropped (see 5.5) and the table is no longer used.

**External integrations.** One: the OpenAI API, used in three places — field extraction, description comparison, and the chat widget.

**Authentication.** Registration stores a bcrypt hash. Login opens a PHP session holding the user id, username, email and role. Every protected page checks for a session at the top and redirects immediately if it is missing.

**Main interfaces.** A public landing page, how-it-works and why-it-helps pages, the Privacy Policy and Legal Notice, registration and login, the user dashboard, and the admin area.

**Client side.** HTML5, CSS3 and vanilla JavaScript with no frameworks. JavaScript handles form behaviour, the EN/ES toggle, and the polling that keeps the admin crawler log live.

**Deployment.** Developed and demonstrated on a local Apache and MySQL stack installed via Homebrew on macOS. The application is designed so that it could be deployed to a normal server environment such as the Windows Server used in class.

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

**Technical complexity.** The project is genuinely more complex than a CRUD exercise, but the complexity is concentrated in two components — the crawler and the parser — rather than spread evenly across the system. The rest, being database logic and a web interface, sits well within what the course covers. That concentration was itself a feasibility argument: it meant the risk was identifiable and could be attacked first.

**Technologies needed and knowledge available.** PHP, MySQL, HTML, CSS and JavaScript were all covered during the course. Python was already familiar. The genuinely new knowledge was integrating a language model API and designing prompts that return reliably structured data, which had to be learned during the project. That turned out to be the right size of gap: large enough to be worth learning, small enough not to threaten delivery.

**Hardware and software availability.** Everything needed was already available. The project was developed entirely on a personal MacBook Pro M3 with a local Apache and MySQL stack via Homebrew. No server had to be bought or rented at any point, and every tool used — VS Code, Python 3, PHP, MySQL, Git — is free.

**External services and cost.** One paid service, the OpenAI API, at roughly $0.0003 per comparison call. Total spend across the entire project was about 25 cents. This is the critical feasibility point, and it is worth explaining why.

A conventional web project runs comfortably on a small server with 2–4 GB RAM, 60–80 GB of storage and a standard i3–i5 CPU, costing somewhere between 3.60 € and 20 € per month, or around 6–12 € for typical student hosting. Adding AI changes that picture considerably: running models locally often demands GPUs, monthly costs can easily reach 20–50 €, and professional hardware such as an RTX 4090 costs around 250 € or more. Using a hosted API instead of self-hosting a model, and calling it sparingly, is what keeps this project in the first cost bracket rather than the second.

That is the reasoning behind the two-stage comparison. All the cheap, deterministic filtering is done in SQL first, and AI is applied only once the candidate list has been narrowed. Data is analysed once, stored, and reused rather than reprocessed on every request. The result is a project that is technically current without being expensive to run — which matters, because a system that works but costs too much per query is not a product.

**Limitations and technical risks.** The crawler and parser depend on the HTML structure of third-party sites, which can change without notice. The pipeline depends on the OpenAI API remaining available and affordably priced. Only one real site could legally be crawled, which is why a demo portal of fake listings was built for testing.

**Time available.** The project was developed over roughly two and a half months, working intermittently at around 1.5 hours per day on active days, against two fixed deadlines. The scope was deliberately limited to what could be completed in that window: a working duplicate-detection pipeline rather than a full commercial platform. Image-based matching was excluded for exactly this reason.

**Conclusion.** The project was feasible, and it was in fact completed within the time available and to both deadlines.

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

The project ran from the end of January 2026 to the final delivery on **25 March 2026**, with a fixed intermediate checkpoint at the public presentation on **27 February 2026**.

| Phase | Period | Objective | Main tasks | Deliverable | Depends on |
|---|---|---|---|---|---|
| 1. Analysis and data design | Weeks 1–2 | Establish the data structure | Sketch the schema on paper; define how components relate; decide what data flows where | Paper design + first SQL schema | — |
| 2. Environment setup | Week 1 | A working local stack | Homebrew Apache/MySQL, VS Code, Python 3, PHP, Git repository on GitHub, OpenAI account | Running dev environment | — |
| 3. Crawler | Weeks 1–7 | Reliably fetch and store listing pages | Five successive versions; single-URL and sitemap modes; polite User-Agent and 1.5 s delay | `python/crawler_v4.py` | 1, 2 |
| 4. Parser | Weeks 1–7 | Extract structured fields from raw HTML | Two to three major iterations; final version uses the OpenAI API | `scripts/openai_parse_raw_pages.php` | 1, 2, 3 |
| 5. Database refinement | Weeks 1–7 | A schema that supports the comparison logic | Second and final schema version | `data/sql/test2firstlisting.sql` | 1, 3, 4 |
| 6. Comparison logic | Weeks 5–7 | Identify duplicates | Weighted SQL scoring; AI description comparison on the top candidates | `scripts/find_duplicates.php`, `scripts/ai_compare_descriptions.php` | 4, 5 |
| 7. Test data | ~Week 6 | Predictable, varied test cases | Build five fake portal pages; write a seeding script | `data/demo-portal/` | 3, 4 |
| 8. **Checkpoint — presentation** | **27 Feb 2026** | Demonstrate a working prototype | Live demo of the pipeline, not slides | Public presentation | 3–7 |
| 9. Front-end and admin panel | Weeks 7–10 | Make the system usable | HTML, CSS, vanilla JS; user dashboard; admin panel; EN/ES toggle | `public/` | 6 |
| 10. Refinement | After 27 Feb | Turn a prototype into a finished product | Code cleanup; token reduction; crawler and parser improvements; UI upgrade | Final codebase | 8, 9 |
| 11. **Documentation and delivery** | **To 25 Mar 2026** | Deliver | Project report; GitHub repository; legal pages | Final delivery | 10 |

Phases 3, 4 and 5 overlap deliberately. Rather than moving linearly, I worked in continuous iterations — refining the parser, then the crawler, then both together — because a fault in one only became visible once the next consumed its output.

By around **20 February**, three weeks into the project, the core pipeline existed in rough form and could run end to end: a basic crawler, a basic parser, and a first iteration of the MySQL database. Nothing was polished, but it worked, and that was the state taken into the presentation. Between the presentation and the final delivery the work shifted entirely to refinement rather than new features.

## 3.4. Objectives and scope

Define the specific objectives you intend to achieve.

The objectives must be verifiable. Instead of writing only "create a good application," it is preferable to establish objectives such as "implement an authentication system with two user profiles," "allow CRUD operations on the main data" or "deploy the application on a server accessible via browser."

You must also define the **scope**: what the project includes and what is explicitly left out.

Defining the boundaries is important to prevent the project from growing uncontrollably during its development.

**ANSWER**

**Objectives.** Each is stated so that it can be verified by inspection of the delivered system rather than judged by impression.

| # | Objective | How it is verified |
|---|---|---|
| O-1 | Implement authentication with three distinct user roles | Register and log in as `agent`, `private` and `admin`; confirm the admin area rejects non-admins |
| O-2 | Store all project data persistently in a relational database | Six tables present and populated after a run |
| O-3 | Crawl an arbitrary listing URL and store its raw content | Submit a URL; confirm a new raw page row containing the HTML |
| O-4 | Extract structured fields from raw HTML using AI | Confirm a listings row with price, surface area, rooms, bathrooms, type and reference |
| O-5 | Score stored listings against a submitted one using weighted SQL | Confirm scored candidates ordered by score against the 17-point maximum |
| O-6 | Compare descriptions semantically using AI for the strongest candidates | Rewrite a description and confirm the listing is still matched |
| O-7 | Present results ranked in a usable interface | Submit a URL and read the results table |
| O-8 | Provide an admin panel with full CRUD | Create, read, update and delete records from the admin area |
| O-9 | Provide the interface in at least two languages | Toggle EN/ES and confirm the interface changes |
| O-10 | Keep AI cost per run low enough to be commercially plausible | Token usage per run measured at 2,000–5,000, down from 15,000–20,000 |
| O-11 | Maintain the project under version control with visible history | Commit history in the public GitHub repository |
| O-12 | Comply with GDPR in the data the application collects and stores | Privacy Policy and Legal Notice published; minimum data collected; passwords hashed |

**Scope — included.** The full pipeline from URL to ranked duplicate list; three user roles; the user dashboard; the administration panel with CRUD, raw-data viewer and live crawler log; the bilingual interface; the AI chat widget; the legal pages; a demo portal of five fake listing pages for testing; and the project documentation and public repository.

**Scope — explicitly excluded.** Defining these boundaries is what kept the project deliverable.

- **Image-based matching.** Comparing photographs to identify the same property was considered at the outset and deliberately left out. It would add significant complexity and cost for a benefit that text and numeric comparison already largely delivers.
- **Genuine publication dates.** The system orders by when the crawler first encountered a listing, not by when it was actually published. Recovering true publication dates would require data the portals do not reliably expose. This is the single most important limitation to state honestly, because "which came first" is the product's headline claim.
- **Large-scale crawling of live portals.** Only one real site could legally be crawled, so the system was tested against that site plus a purpose-built demo portal.
- **Production deployment.** The project runs on a local stack. It is designed to be deployable, but a live public deployment with firewall configuration, automated backups and server hardening was not part of the scope.
- **Payment processing.** A `subscriptions` table exists, but no billing integration was built.
- **A mobile application**, for the reasons given in 2.5.

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

These are preparatory activities rather than functional phases of the software.

- **Prepare the development machine.** A personal MacBook Pro M3 was used; no additional hardware was acquired.
- **Install the local stack.** Apache and MySQL through Homebrew, plus PHP and Python 3, so the application could run locally without any external server.
- **Install the development tools.** VS Code as the editor, Git for version control.
- **Set up the repository.** A Git repository created and pushed to GitHub, which was also a requirement of the course.
- **Create the OpenAI account and obtain an API key.** The key is held in a local environment file kept out of version control.
- **Design and create the database.** Writing and applying the SQL schema, then revising it once when the first structure proved insufficient.
- **Obtain crawling permission.** Permission for the primary test site was requested from, and granted by, the site owner. This was a genuine prerequisite rather than a formality — without it there would have been no real site to test against at all.
- **Build the demo portal.** Five fake listing pages were written specifically for testing, because one real site could not produce the varied near-duplicates the system is designed to detect.
- **Seed test data.** A one-off seeding script was written to populate the database with known duplicates, so the scoring logic could be checked against expected outcomes rather than guessed at.

Activities that appear on the general list but are **not** applicable to this project: no server was hired, no domain was registered, no paid licences were bought, and no graphic or multimedia resources were needed beyond the company logo, which was generated with AI tools.

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

| Resource | Detail | Cost |
|---|---|---|
| Development computer | MacBook Pro M3 (personal) | Already owned; ~1,500 € to acquire |
| Server | None — local Apache via Homebrew | 0 € during development |
| Operating system | macOS | Included with the hardware |
| Web server | Apache (Homebrew) | Free / open source |
| Database | MySQL / MariaDB | Free / open source |
| Languages and runtimes | PHP, Python 3 | Free / open source |
| Editor | Visual Studio Code | Free |
| Version control | Git, repository hosted on GitHub | Free |
| External API | OpenAI GPT-4.1-mini | ~$0.0003 per call; ~0.25 € total across the whole project |
| Hosting and domain | Not used during development | 0 € now; 500–1,000 € per year in a real launch |
| Graphic resources | Company logo, generated with AI tools | 0 € |

Everything except the OpenAI API is free or open source. That does not make these resources cost-free in a real setting — they still have to be installed, configured and maintained — which is why they are listed here even though no money changed hands.

In a commercial launch the additional recurring costs would be hosting and cloud services (500–1,000 € per year) and utilities such as internet, electricity and software tools (roughly 250 € per month, about 3,000 € per year). With telework there is no office rent, giving a realistic total of around **7,000–7,500 € of material cost in the first year**, and less in the second unless growth demanded more.

### Personnel resources

Indicate what professional profiles would be necessary if the project were carried out in a real business context: development, design, systems, testing, project management, support, etc.

If the academic project is carried out by a single person, you may state so, but you must be able to analyze what human resources an equivalent professional execution would require.

**ANSWER**

In practice the project required exactly one person. I carried out every role: analysis, database design, crawler development, AI integration, backend, frontend, admin panel, testing, documentation and the public presentation.

In a real professional setting the same product would require the following profiles:

| Profile | Responsibility | Realistic dedication |
|---|---|---|
| Project manager / Product Owner | Scope, priorities, client relationship | Part-time |
| Backend developer | Web application, database access, pipeline orchestration | Full-time |
| Data engineer | Crawler and parser — the hardest and most fragile components | Full-time during acquisition work |
| Frontend developer | Interface, admin panel, internationalisation | Part-time |
| Database designer | Schema design and optimisation | Short, concentrated at the start |
| QA / tester | Functional and regression testing | Part-time throughout |
| Systems / DevOps | Deployment, backups, monitoring | Part-time, mainly at deployment |
| Legal and tax adviser | GDPR compliance, contracts, invoicing | Outsourced |

In the realistic micro-enterprise version described in 2.2, one founder covers the first seven of these and the last is outsourced from the beginning.

The exercise is worth doing even though the project was individual, because it shows where the workload actually concentrates. The data engineer role spans almost the entire project and owns the two most fragile components; the frontend role is compressed into the final quarter. That is why a second hire in a scaled company would sensibly be a second data engineer rather than a frontend developer. For costing purposes a single full-time equivalent is assumed at a gross annual cost of **24,000 €**, which is the minimum realistic personnel expense even in a one-person company.

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

**During development: essentially none.** The project was built on hardware I already owned, using free and open-source software, on a local stack with no server or domain, and the only external service cost about 25 cents in total. No financing was required to reach a working product.

**To launch commercially: yes, but not a large amount.** The realistic first-year figures are:

| Item | First-year cost |
|---|---|
| Founder salary (gross) | 24,000 € |
| Computer equipment | 1,500 € |
| Hosting and cloud services | 500–1,000 € |
| Internet, electricity, software tools (~250 €/month) | 3,000 € |
| Office rent (telework) | 0 € |
| OpenAI API | Negligible at current volume |
| **Total** | **≈ 31,500 €** |

Against that, at 30 € per month each client pays 360 € per year, so roughly **88 paying clients** are needed to break even — about 10 % of the 850–900 agencies in the target area, which is ambitious for a first year. A more realistic first-year scenario of 40–60 clients produces 14,400–21,600 €, meaning a first-year loss.

**Conclusion.** FirstListing would probably not be profitable in year one if a full salary is drawn. But the salary is the overwhelming majority of the cost and it is also the most flexible part: I would not be working on this full time, so I would not need a full salary from it. Stripping the salary out leaves roughly 7,500 € of genuine cash requirement, which is low enough to self-finance or to cover with a modest amount of external support.

The project therefore does not depend on raising significant capital. It depends on how much of my own unpaid time I am willing to invest before revenue arrives — which is a different kind of risk, and one I can actually control.

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

**Documentation consulted**

- **Technical documentation for the languages and tools used** — PHP, Python 3, MySQL and Apache, for syntax, standard library behaviour and local configuration.
- **OpenAI API documentation** — request and response formats, model selection, and token accounting. The token documentation in particular directly drove the cost optimisation work.
- **GDPR** — reviewed specifically to confirm the application's data handling is compliant, to identify the correct legal basis (Art. 6.1.b), and to understand the obligations around retention, erasure and third-party processors.
- **Spanish LSSI requirements** — for the required content of the Legal Notice.
- **The structure of real listing pages.** A significant and easily overlooked part of the design work was reading the HTML and JSON-LD of actual listing pages to establish what could reliably be extracted and what could not. The schema was shaped by that reading rather than by an idealised model of what a property record should contain.

**Documentation produced**

- The requirements and scope definition. In the absence of a client brief, this report serves that role (see 5.7).
- The database model — sketched on paper first, then written as an SQL schema.
- The design of the four-step pipeline, and the field weightings used in the scoring.
- The Privacy Policy and Legal Notice published on the site.
- Technical documentation of the codebase, generated automatically from the project directory with AI summaries of each file and folder, producing a Markdown report containing the full directory tree, all source code, and inline summaries of each component. This is published in the GitHub repository.

The purpose of listing this is to show that the design was not done purely by intuition. The two decisions that most shaped the project — using a hosted AI model rather than a self-hosted one, and filtering in SQL before calling AI at all — both came directly out of reading cost and token documentation, not out of guesswork.

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

For a project of this size built by one person, a full SCRUM or Kanban setup would have been overkill. Quality was managed instead through consistent personal organisation, a physical notebook, Git, and disciplined testing. The criteria used to judge it were:

**Compliance with requirements.** Every objective in 3.4 has a stated means of verification, and the project is judged against that list rather than against a general impression of completeness.

**Correct operation and absence of serious errors.** Code quality was maintained through rigorous daily testing. After every function was implemented it was tested — first independently, then integrated with the rest of the system. Each major component (crawler, parser, SQL scoring, AI comparison) was verified both in isolation and as part of the full pipeline.

**Predictable test conditions.** Because only one real site could legally be crawled, a demo portal of five fake local listing pages was built specifically for testing. This provided a controlled set of known listings producing predictable and varied results, simulating how the crawler and duplicate detection would behave in a real scenario. Without it, "does the matching actually work?" would have had no objective answer.

**Code quality and maintainability.** A clear folder structure, consistent naming, commented code, and parameterised database access throughout. Readability is a real requirement here rather than a nicety, because the crawler and parser are known to need ongoing adjustment.

**Traceable history.** Git was used throughout. Every meaningful change was committed with a message, making it possible to review the history, roll back a break, and keep a record of what was done and when. One honest note: the commit messages are not always as descriptive as they should be, which is something to improve in future projects.

**Cost efficiency.** Token usage per run is a genuine quality measure for this project, because an accurate system that is too expensive to run is not a viable product. It was reduced from 15,000–20,000 tokens per run to 2,000–5,000.

**Security.** PDO with parameterised queries, bcrypt password hashing, and session-based authentication checked at the top of every protected page.

**Meeting deadlines.** Two fixed dates — the presentation on 27 February and the final delivery on 25 March — acted as natural checkpoints that forced each phase to reach a functional state before the next began.

**Documentation.** A complete repository report covering the directory tree, the source code, and per-component summaries.

All of these are verifiable after the fact, which is the point: each corresponds to an indicator in 5.2 with a measured result rather than a claim.

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

**Sequential chain — each step blocks the next:**

1. **Design the data model.** Everything downstream writes into it, so getting the structure roughly right first avoided reworking every other component later.
2. **Create the database.** The crawler needs somewhere to write raw pages before it can be tested meaningfully.
3. **Build the crawler.** Produces the raw HTML the parser consumes. Nothing downstream can be tested without real stored pages.
4. **Build the parser.** Converts raw HTML into structured fields. The scoring logic is meaningless without populated, comparable rows.
5. **Build the SQL scoring.** Produces the ranked candidate list.
6. **Add the AI description comparison.** Runs on the output of step 5. It is deliberately last in the pipeline because it operates only on the narrowed candidate set — that ordering is the cost control described in 3.1.
7. **Build the front-end.** Presents the results of steps 1–6. Built last, and correctly so: there was no point designing a results table before knowing what the results actually looked like.
8. **Integrate and test the whole pipeline end to end.**

**Work that ran in parallel or iteratively:**

- Steps 3, 4 and 5 were not completed once each and then left alone. They were developed against each other in continuous iteration — refining the parser, then the crawler, then both together — because faults in one only became visible when the next consumed its output. The crawler went through five versions and the parser through two or three before either was satisfactory.
- The demo portal was built alongside steps 3 and 4, as soon as it became clear that one real site was insufficient for testing.
- Documentation and Git commits ran continuously throughout.
- Refinement of the crawler, parser and interface continued after the presentation checkpoint, in parallel with each other.

The one deviation from a textbook order is that the database schema was revised once mid-project. That is less a planning failure than an acknowledgement that the right schema only became fully clear after the parser revealed what the extracted data actually looked like in practice.

## 4.2. Resources and logistics needed for each task

Relate the main tasks to the resources they need.

A deployment task may need a server; a mobile test may require devices or emulators; an integration may need API credentials; a design phase may need certain tools.

The question you must answer is: **what do I need to have ready in order to correctly execute each task?**

**ANSWER**

| Task | What had to be ready before it could start |
|---|---|
| Data model design | Pen and paper; an understanding of what a real listing page contains |
| Database creation | Local MySQL running via Homebrew |
| Crawler development | Python 3; a target site; **written crawling permission from the site owner**; the raw pages table |
| Parser development | Stored raw pages; an OpenAI account and API key; the listings table |
| SQL scoring | A populated listings table with several comparable records; seeded test duplicates |
| AI description comparison | Extracted descriptions; the candidate list from the scoring step; API credentials |
| Testing the matching logic | The demo portal of five fake listing pages with known relationships |
| Front-end and admin panel | Local Apache serving PHP; working pipeline output to display |
| Live crawler log | The crawler writing to a log file the admin page can poll |
| Internationalisation | Finished interface text to translate |
| Version control and delivery | Git installed; GitHub repository created and connected |
| Legal pages | GDPR and LSSI requirements reviewed |

Two of these were genuine blockers rather than routine setup. The **crawling permission** had to be obtained before any real data could be collected at all. The **demo portal** had to exist before duplicate detection could be tested properly, because a single real site cannot produce the varied near-duplicates the system is designed to find. Both were resolved early enough not to delay the project, but neither was optional, and both are easy to overlook when planning because neither is a line of code.

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

**Crawling permission — required and obtained.** This is the only permission the project genuinely needed. Reading and storing pages from a third-party website is governed by that site's terms, so permission for the primary test site was requested from, and granted directly by, the site owner. Alongside the permission the crawler behaves politely: it identifies itself with a clear User-Agent and waits 1.5 seconds between requests, so it places no meaningful load on the site. This constraint is also why the demo portal exists — rather than crawl sites I had no permission for, I built five fake listing pages to test against.

**OpenAI API credentials — required and obtained.** A standard commercial account. The API key is stored in a local environment file excluded from version control.

**Data protection — obligations, not authorisations.** This distinction matters and is easy to get wrong. No authority has to approve FirstListing's data processing, and there is no registration or prior authorisation requirement for processing of this kind. What exists are **obligations** under GDPR, which the project meets by collecting only the minimum necessary data (username, optional email, bcrypt-hashed password, and the URLs searched), by processing on the legal basis of performance of a contract (Art. 6.1.b), by publishing a Privacy Policy and Legal Notice covering legal bases, retention and erasure rights, and by identifying OpenAI as the sole third-party processor.

**Not required.**

- No permission to access any company's internal systems — the project reads only public web pages.
- No app store or publication approval, since this is a web application.
- No consent for user testing, because no testing with real users was carried out (see 5.6).
- No paid licences, since the entire stack is free and open source.
- No sector-specific real estate authorisation, because the tool is a B2B analysis product aimed at private users, does not interact with public administrations, and does not handle transactions.

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

**Version control.** Git throughout, with the repository on GitHub. Every meaningful change was committed with a message, so the history can be reviewed and a broken change rolled back. I worked directly on the main branch rather than using a branching strategy — with a single developer and no concurrent work, branches would have added ceremony without benefit. One weakness I will name openly: the commit messages are not always as descriptive as they should be.

**Backups.** The GitHub remote is the backup. Pushing regularly means the project exists in at least two places at all times, which matters given that hardware failure is one of the identified risks.

**Secret handling.** The API key lives in a local environment file excluded from version control, so credentials never reach the repository.

**Testing before integrating.** The central discipline of the project. After every function was implemented it was tested — first in isolation, then integrated with the rest of the system. Each major component was verified on its own and again as part of the full pipeline. This is the reason a fragile system that went through five crawler versions and three parser versions never ended up in an unknown state.

**Controlled test data.** The demo portal of five fake listing pages provided a known, repeatable set of inputs, so a change to the scoring could be judged against expected results rather than estimated by eye.

**Naming and structure.** Consistent file and folder naming, with a clear separation between `public/`, `scripts/`, `python/`, `config/` and `data/`, and commented code throughout.

**Task tracking.** A physical notebook rather than Trello or a project management tool. Writing down ideas, structures and what needed doing next was sufficient, and it was particularly useful in the early stages for sketching timelines and thinking through how the components should fit together. With clear deadlines, one developer and regular commits, a notebook and Git served the same purpose a formal tool would have.

**Deliverable validation.** The two fixed deadlines — the presentation on 27 February and the final delivery on 25 March — acted as checkpoints, each requiring the system to be in a demonstrable state rather than merely in progress.

**Procedures deliberately not used.** No code review (there was no second developer), no formal branching strategy, no CI pipeline, and no formal release versioning. These add real value on a team; on a solo project of this length they would have been overhead without benefit.

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

| # | Risk | Prob. | Impact | Prevention | Response if it occurs |
|---|---|---|---|---|---|
| R-01 | A target site changes its HTML structure and the crawler breaks | High | High | Keep crawler logic isolated and readable; avoid over-fitting to one layout | Adjust extraction for that site; the demo portal confirms the rest still works |
| R-02 | Adding a new site is harder than expected — different structures, different amounts of raw text, different JSON-LD implementations | High | Medium | Use AI extraction rather than per-site hardcoded rules | Tune the prompt; accept slower per-site onboarding |
| R-03 | OpenAI API becomes unavailable or changes pricing | Low | Very high | Keep AI calls concentrated in two scripts so the provider can be swapped | Substitute another provider; pure-code parsing is not a realistic fallback |
| R-04 | Token usage climbs and cost becomes unviable | Medium | Medium | Filter in SQL before calling AI; cap the AI comparison at the top 5 candidates; monitor tokens per run | Re-optimise prompts — already done once, from 15,000–20,000 down to 2,000–5,000 |
| R-05 | SQL injection | Low | High | PDO with parameterised queries everywhere | Patch and review all query construction |
| R-06 | Session hijacking or unauthorised access | Low | High | Session-based auth with a check at the top of every protected page; bcrypt password hashing | Invalidate sessions; review authentication |
| R-07 | Data loss or hardware failure | Low | High | Regular commits and pushes to GitHub | Restore from the remote repository |
| R-08 | Data growth and corruption — stored HTML is currently 20,000–25,000 characters per page and could reach 100,000 if crawler scope widened | Medium | Medium | Keep the crawler scope tight; store only what is needed | Trim stored content; introduce retention limits |
| R-09 | Schedule slip against the two fixed deadlines | Medium | High | Deliberately limited scope; front-end left until last so the core pipeline was proven first | Cut scope rather than move the deadline — image matching was excluded for exactly this reason |
| R-10 | Electrical risk from badly installed cabling | Low | High | Safe cable installation; a 220 V connection can cause serious injury or fire, and low-voltage devices can cause minor shocks | Standard electrical safety and first aid procedure |
| R-11 | Ergonomic risk — back pain, eye strain, carpal tunnel syndrome, burnout | High | Medium | Ergonomic workspace, correct screen height, regular structured breaks | Adjust the workstation and reduce session length; untreated, these lead to sick leave and financial loss |
| R-12 | Loss or theft of the development machine | Low | High | The remote repository holds the code | Restore from GitHub onto replacement hardware |

The two weakest points in the system are the crawler and the parser, since they are by far the most complex parts of the project. **R-01 and R-02 are therefore the risks most likely to actually materialise**, and they would be the main scaling problem if the project grew beyond its current set of sites. R-03 is the opposite profile — unlikely, but it would break the core feature outright, which is why it is worth naming even though nothing in the current design mitigates it fully.

On IT security specifically: because the project runs locally rather than on a live server, measures such as firewall configuration and server updates are not directly applicable at this stage. If the project were deployed to production they would become standard requirements — keeping the operating system and dependencies updated, configuring a firewall, and setting up regular automated backups.

## 4.6. Allocation of material and human resources according to execution timing

Relate the time planning to the resources.

Indicate what resources are needed in each phase, for how long, and which professional profile would be responsible in a professional scenario.

Even though the academic project is individual, this exercise allows you to demonstrate that you are capable of planning the project from a professional perspective.

**ANSWER**

| Phase | Approx. period | Human resource (professional equivalent) | Material resources |
|---|---|---|---|
| Analysis and data design | Weeks 1–2 | Database designer + Product Owner | Pen and paper; computer |
| Environment setup | Week 1 | DevOps / systems | MacBook, Homebrew stack, VS Code, Git, GitHub, OpenAI account |
| Crawler | Weeks 1–7 | Data engineer | Python 3; target site with permission; MySQL |
| Parser | Weeks 1–7 | Data engineer + AI integration | PHP; OpenAI API; stored raw pages |
| Database refinement | Weeks 1–7 | Database designer | MySQL |
| Comparison logic | Weeks 5–7 | Backend developer | PHP, MySQL, OpenAI API |
| Demo portal and test data | ~Week 6 | QA / tester | HTML; seeding script |
| Front-end and admin panel | Weeks 7–10 | Frontend developer | HTML, CSS, JS; local Apache |
| Refinement and optimisation | After 27 Feb | Backend + data engineer | Full stack |
| Documentation and delivery | To 25 Mar | Technical writer + Product Owner | Documentation tooling; GitHub |
| Legal and compliance | Throughout | Outsourced legal adviser | GDPR and LSSI reference material |

In reality one person — me — filled every column. The exercise of assigning professional profiles is still worth doing, because it makes visible where the workload actually concentrates: the **data engineer** role spans almost the entire project and owns the two most fragile components, while the **frontend** role is compressed into the final quarter. In a real team that is where the staffing would go, and it is also why a second hire in a scaled company would sensibly be a second data engineer rather than a frontend developer.

Total effort is estimated at around **375 hours** — roughly five months at an average of 1.5 hours per day, covering the two and a half months already spent plus an estimated further two and a half months to reach genuine production quality.

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

**Criteria used.** The figures below assume the project is developed in a professional context rather than as a student exercise, so my own time is costed at a market rate even though no money changed hands. Development effort is estimated at 1.5 hours per day over five months — the two and a half months already invested plus an estimated further two and a half months to reach real production value — giving roughly 375 hours. Equipment is costed at acquisition value rather than amortised, since the project spans less than a year. Hosting and utilities are included at realistic market rates even though the project actually ran locally at zero cost.

**Development cost (one-off)**

| Item | Basis | Cost |
|---|---|---|
| Development effort | 375 h × 20 €/h | **7,500 €** |

**First-year operating cost**

| Item | Basis | Cost |
|---|---|---|
| Founder salary (gross) | Minimum realistic annual personnel expense | 24,000 € |
| Computer equipment | MacBook, acquisition value | 1,500 € |
| Hosting and cloud services | Annual | 500–1,000 € |
| Internet, electricity, software tools | ~250 €/month | 3,000 € |
| Office rent | Telework | 0 € |
| OpenAI API | ~$0.0003 per comparison call | Negligible at current volume |
| **Materials subtotal** | | **≈ 7,000–7,500 €** |
| **Total first year** | Salary + materials | **≈ 31,500 €** |

**Revenue and break-even.** At 30 € per month each client pays 360 € per year, so 31,500 € ÷ 360 € ≈ 88 clients.

| Scenario | Clients | Annual revenue | Result |
|---|---|---|---|
| Break-even | ~88 (≈10 % of market) | ~31,680 € | Costs covered |
| Realistic year one | 40–60 | 14,400–21,600 € | Loss |
| 25 % penetration | ~225 | ~81,000 € | Comfortable profit |

**Interpretation.** The dominant cost is labour: salary alone is roughly 76 % of the first-year total, and development effort at market rate accounts for a further 7,500 €. Actual cash spent on this project to date is close to zero — hardware already owned, free and open-source software, no server, and about 25 cents of API usage.

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

The documents that genuinely support execution, as opposed to restating this report:

- **Requirements and scope definition** — the functional and non-functional requirements in 2.6 and the objectives in 3.4, used as the checklist for deciding when something is finished.
- **Data model** — the SQL schema file, which is the authoritative description of the six tables and what every new component is written against.
- **Pipeline specification** — the four ordered steps, what each consumes and produces, and the exact commands used to invoke them, since the web application calls the crawler and the scripts directly.
- **Scoring specification** — the field weightings and the cut-off, documented separately from the code because they are tuning parameters that are expected to change.
- **Planning** — the phase table in 3.3 and the task sequence and dependencies in 4.1.
- **OpenAI API documentation** — consulted continuously during parser and comparator work, particularly for token accounting.
- **Deployment and setup instructions** — how to install the local stack, create the database from the schema, and configure the API key. Needed by anyone rebuilding the environment from scratch, including me on a new machine.
- **Test plan and test data** — the demo portal pages and the seeding script, together with a record of which listings are expected to match which.
- **Incident and change record** — the physical notebook and the Git history (see 5.3 and 5.5).
- **Technical documentation of the codebase** — generated automatically from the project directory with AI summaries of each file and folder, producing a Markdown report containing the full directory tree, all source code and inline summaries. Published in the GitHub repository.
- **Legal documentation** — the Privacy Policy and Legal Notice.

Two manuals complete the set. A **developer manual** covering system requirements, local environment setup (Apache, MySQL, Python 3 and PHP via Homebrew), creating the database from the provided schema, configuring the OpenAI API key, the project structure, the four-step pipeline in detail, the database schema and the reasoning behind key design decisions, and how to run the crawler in both single-URL and sitemap bulk modes. And a **user manual**, much simpler and aimed at non-technical agents, covering how to register and log in, how to submit a listing URL and understand the results, what the duplicate score means, what the AI confidence badges indicate, and the known limitations of the system — particularly that "first seen" refers to when the crawler first encountered the listing, not when it was originally published.

That last point is the single most important line in the user manual. A user who reads "first seen" as the genuine publication date will draw a wrong conclusion from a correctly functioning system, so the documentation has to carry a limitation the software cannot yet remove.

------------------------------------------------------------------------

# 5. Monitoring and control

The fourth learning outcome consists of **defining procedures for monitoring and controlling project execution, justifying the variables and instruments used**.

A professional project does not end with an initial plan. During its development we must check whether the work is progressing correctly, log problems, manage changes and evaluate the results.

**ANSWER**

This section describes how progress and quality were actually monitored during the project. It is deliberately honest about the difference between what a professional process would look like and what a single student developer working for two and a half months actually did. Where the real process was informal, I say so and set out what the formal equivalent would be, rather than describing a procedure I did not follow.

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

**The general rule.** A task was considered finished when the component it produced worked in isolation and then continued to work as part of the full pipeline. This two-stage check was applied after every function was implemented, and it is the single procedure that kept a fragile system stable across five crawler versions and three parser versions.

**Acceptance criteria by component.**

| Component | Considered finished when |
|---|---|
| Database schema | All tables create cleanly and the pipeline can write to every one of them |
| Crawler | Given a URL it fetches the page, stores the HTML, and returns a usable raw page id — for both the permitted real site and the demo portal |
| Parser | Given a stored raw page it produces a listings row with the expected fields correctly populated |
| SQL scoring | Given a seeded set of known duplicates it returns the expected candidates with the expected scores |
| AI comparison | It correctly identifies a rewritten description as the same property |
| Full pipeline | A single URL submission runs all four steps and displays a result within the time limit |
| Front-end | Every page renders, forms behave, the EN/ES toggle works, and protected pages reject unauthenticated access |
| Admin panel | Create, read, update and delete all work; the raw viewer and live crawler log display correctly |

**Predictable verification data.** The demo portal exists precisely so that "did the matching work?" has an objective answer. Five fake listing pages with known relationships mean there is an expected result to compare against, instead of a subjective judgement about whether two properties look similar. This is the difference between testing and hoping.

**Checkpoint demonstrations.** The two fixed deadlines functioned as formal acceptance points. The 27 February presentation required a working prototype demonstrated live rather than described in slides, which is a considerably harder test than a written status report. The 25 March delivery required the finished system. Each forced the project into a demonstrable state at a known date.

**Honest limitation.** These criteria were applied consistently, but they were held in a notebook and in my head rather than written as a formal signed-off acceptance document. On a team project, or one with a client, they would need to be written down per task and agreed in advance.

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

| Indicator | Target / criterion | Actual result |
|---|---|---|
| Requirements implemented | All functional requirements in 2.6 delivered | All 14 delivered |
| Objectives achieved | All objectives in 3.4 verifiable on the delivered system | All 12 verifiable |
| Pipeline success rate | A submitted URL completes all four steps and returns a result | Achieved for the permitted real site and all five demo portal pages |
| Duplicate detection accuracy | Known seeded duplicates are found; unrelated listings are not returned | Verified against the demo portal's known relationships |
| Response time | Full pipeline completes within the 120-second request limit | Within limit |
| **Token usage per run** | As low as possible without losing accuracy | Reduced from 15,000–20,000 to **2,000–5,000** |
| **AI cost per comparison** | Low enough to be commercially viable | ~$0.0003 per call; ~0.25 € total across the whole project |
| Stored HTML per page | Kept bounded | 20,000–25,000 characters |
| Deadlines met | Both fixed dates | Presentation 27 Feb and delivery 25 Mar both met |
| Version control coverage | Every meaningful change committed | Full history in the public repository |
| Security conventions | Parameterised queries, hashed passwords, auth check on every protected page | Applied throughout |
| Internationalisation | At least two languages | English and Spanish |
| Documentation coverage | Every file and folder documented | Full automated report with per-component summaries |
| Code conventions | Consistent naming, clear structure, commented code | Applied; commit message quality is the known weak point |

**Why these indicators.** Two deserve emphasis because they are specific to this project rather than generic.

**Token usage per run** is a quality indicator here in a way it would not be for an ordinary web application. An accurate system that costs too much per query is not a sellable product, so reducing consumption from 15,000–20,000 to 2,000–5,000 tokens is a quality improvement rather than merely an optimisation — it is what moves the product from technically working to commercially plausible.

**Detection accuracy against known duplicates** is the indicator that actually measures whether the product does its job, and it is only measurable at all because the demo portal provides a ground truth to compare against. Without that, every other indicator in the table could be green while the core feature quietly failed.

**Indicators deliberately not used.** Accessibility compliance was not formally measured. Incidents detected by users were not measurable, because there were no real users (see 5.6). Automated test coverage was not measured, because testing was manual rather than through a test suite — a reasonable choice at this scale, but one that would not survive the project growing.

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

**What actually happened.** Incidents were recorded informally, through two mechanisms. The **physical notebook** held problems, ideas and what needed doing next, and was especially useful in the early stages. The **Git history** is the more durable record: every meaningful change was committed with a message, so a problem and the change that resolved it are both traceable, and a broken change can be rolled back. There was no separate incident register, and no tool such as Trello or a bug tracker was used. With one developer, clear deadlines and regular commits, this was sufficient in practice.

**Honest assessment.** This works for a solo project but would not survive a team or a live product. Nothing records *why* a change was made if the commit message is thin — and I have already acknowledged that my commit messages are not always as descriptive as they should be. That is precisely the weakness a proper incident log exists to cover.

**The formal equivalent.** In a professional setting, or if this project continued with real users, incidents would be recorded in a register with the following fields:

| Field | Purpose |
|---|---|
| ID | Unique reference |
| Date detected | When it appeared |
| Description | What was observed, including the input that triggered it |
| Affected component | Crawler, parser, scoring, AI comparison, front-end, admin, database |
| Severity | Blocker / major / minor / cosmetic |
| Cause | Root cause once identified |
| Responsible | Who owns it |
| Status | Open / in progress / resolved / closed |
| Solution applied | What was changed, with the commit reference |
| Resolution date | When it was closed |

**Applying that format retrospectively.** The significant incidents of this project would have been logged as:

| Description | Component | Severity | Cause | Solution applied |
|---|---|---|---|---|
| Field extraction unreliable across differing page structures | Parser | Major | Rule-based parsing cannot generalise over arbitrary HTML | Moved extraction to the OpenAI API |
| Crawler fails or returns unusable content on new sites | Crawler | Major | Different structures, differing amounts of raw text, inconsistent JSON-LD | Five successive crawler versions |
| Cost per run far too high at 15,000–20,000 tokens | Parser / comparator | Major | Oversized prompts and unfiltered input | Prompt optimisation; reduced to 2,000–5,000 tokens |
| Schema does not support the comparison logic as first designed | Database | Major | The first schema was designed before the shape of real extracted data was known | Second and final schema version |

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

The process actually followed, and the one I would formalise:

1. **Detect and record.** The problem is noted in the notebook together with the input that triggered it, so it can be reproduced later rather than recalled vaguely.
2. **Classify priority.** Anything breaking the pipeline end to end is a blocker and is fixed before new work continues. Anything cosmetic, or affecting only the interface, waits until the current component is stable.
3. **Reproduce in isolation.** Run the affected component on its own — the crawler against a single URL, the parser against a single stored page, the scorer against a single listing id. Every script in the pipeline accepts an id argument and can be run independently from the command line, which exists specifically to make this step cheap.
4. **Reproduce against known data.** Where the problem concerns matching rather than mechanics, reproduce it against the demo portal, where the expected result is already known.
5. **Analyse the cause.** Distinguish a fault in my own code from a change in a third party — a site altering its HTML, or the model returning a differently shaped response. These require completely different responses, and misdiagnosing one as the other wastes a lot of time.
6. **Propose and apply a change,** keeping it scoped to the component that is actually broken.
7. **Test in isolation, then integrated.** The standing rule of the project: verify the component alone, then verify the full pipeline still runs end to end. A fix that repairs the parser but breaks the scorer is not a fix.
8. **Commit with a message.** This is what makes the resolution traceable and reversible.
9. **Close.** The incident is closed only once the full pipeline has been re-run successfully.

Two points are specific to this project. Step 3 is only cheap because the pipeline was deliberately built as separate command-line scripts rather than as one monolithic process — a design decision that paid for itself repeatedly during debugging. And step 5 matters more here than in a typical project, because two of the most likely failure causes (a site changing its HTML, the API changing behaviour) are entirely outside my control, and the correct response to those is to adapt rather than to hunt for a bug that does not exist.

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

**How changes were recorded.** The Git history is the change record. Every meaningful change was committed, so the initial plan and what actually happened can be compared by reading the repository. The notebook held the reasoning behind the larger decisions before they were made.

**The significant changes that actually occurred.**

| Period | Element | Initial situation | Change made | Reason | Impact | Decision |
|---|---|---|---|---|---|---|
| Weeks 1–7 | Parser | Parse listing fields in code without AI | Moved to the OpenAI API (GPT-4.1-mini) | Parsing without AI proved at least very complicated and unreliable across differing page structures | Introduced a dependency on an external paid API; made the feature actually work | Accepted — it was the only approach that worked effectively |
| Weeks 1–7 | Crawler | First working version | Five successive versions before one was reliable | Different sites have different structures, different amounts of raw text and different JSON-LD implementations | The largest single consumer of effort in the project | Accepted — nothing downstream works without it |
| Weeks 1–7 | Parser | First version | Two to three major iterations | Extraction quality | Time cost | Accepted |
| Mid-project | Database schema | First schema | Second and final version | The first structure did not fully support the comparison logic once real extracted data existed | Rework of dependent queries | Accepted — cheaper to fix early than to work around indefinitely |
| Mid-project | Test data | Crawl real sites only | Built a demo portal of five fake listing pages | Only one real site could legally be crawled, and one site cannot produce varied near-duplicates | Added a small build task; made the matching logic testable at all | Accepted — it unblocked all downstream testing |
| **2026-03-06** | Description comparison | ChromaDB planned as a vector database for semantic comparison | **ChromaDB removed entirely** and the comparison script deleted | GPT-4.1-mini handled description comparison more accurately and with far less complexity, and already worked | Removed a whole technology from the stack; simplified deployment; left the `vector_matches` table unused | Accepted — the accuracy and simplicity gain outweighed keeping it |
| After 27 Feb | AI prompts | 15,000–20,000 tokens per run | Optimised to 2,000–5,000 tokens | Cost per run was far too high to be commercially plausible | Roughly a fourfold cost reduction | Accepted — cost is a product requirement, not just an optimisation |
| After 27 Feb | Front-end | Basic functional interface for the presentation | Upgraded and cleaned across several iterations | The presentation showed a working prototype; the delivery needed a finished product | Time cost concentrated in the final phase | Accepted |
| Throughout | Scope | Image and location matching considered in the original concept | Left out | Complexity and cost disproportionate to the benefit over text and numeric comparison | Narrowed scope; protected the deadline | Accepted — documented as a future extension |

**Reading the record.** The pattern is consistent and worth stating explicitly: almost every change **simplified** the system rather than extending it. Dropping ChromaDB, excluding image matching, and consolidating extraction into a single AI call all reduced scope in exchange for something that worked reliably within the available time. The one change that went the other way — adopting the OpenAI API — added an external dependency, and it is recorded as the project's single largest risk in 4.5 for exactly that reason.

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

**What was actually done.** No structured testing with real users was carried out. This is a genuine gap rather than something I want to dress up, and it is worth saying why it happened: the project ran for two and a half months against two fixed deadlines, the system needed a working pipeline before it was worth showing to anyone, and the front-end was deliberately built last — which left very little window in which there was something testable to put in front of a real agent.

Two things partially substitute for it. First, the **public presentation on 27 February** was a live demonstration to a real audience, run as a working demo rather than as slides. It covered the purpose of the project, the duplicate detection pipeline and the database structure, and the audience came away with a clear understanding of the project. The main thing that did not go to plan was time: the crawler section had to be cut short. That is feedback about the product's explainability, even if it is not usability testing. Second, the requirements themselves come from **direct experience of the problem** — I identified this need while working in a real agency, so the product is grounded in observed user behaviour even though it was not validated with users afterwards.

**How it should be done in a real scenario.** Evaluation would target the buyer persona identified in the market analysis: real estate back-office staff aged roughly 30 to 45 on the Costa Blanca — the administrators who create the listings rather than the agents who do the selling, since they are the people who deal with duplicates daily and would recognise the value immediately.

| Method | Participants | What it would evaluate |
|---|---|---|
| Usability test, think-aloud | 5–8 back-office administrators | Can they submit a URL and correctly interpret the result without being taught? |
| Accuracy validation | 2–3 agencies, using their own live listings | Does the system find the duplicates they already know about, and does it produce false positives? |
| Semi-structured interview | Same participants, after use | Does this save real time? Would they pay 30 € a month for it? |
| Satisfaction questionnaire | All participants | Clarity of results, confidence in the score, perceived usefulness |
| Comprehension check | All participants | Do they understand what the score means, and that "first seen" is not the true publication date? |

Results would be recorded per participant per task — completion, time taken, errors and verbatim comments — with the accuracy test scored against each agency's own ground truth.

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

**There is no formal specifications document.** FirstListing has no client, no contract and no external pliego de condiciones. It is a self-directed project whose requirements I defined myself, so there is no third-party specification to verify compliance against.

**The equivalent reference.** In its place I use my own requirements and scope definition — the functional and non-functional requirements in 2.6 and the objectives in 3.4. These serve the same function: a fixed, written statement of what the system must do, set down before the work and checked against afterwards.

**Traceability matrix.**

| Requirement | Implemented by | Developed in | Verified by | Status |
|---|---|---|---|---|
| RF-01 Registration and login | `register.php`, `login.php` | Phase 9 | Register and log in; confirm the stored password is a hash | Complete |
| RF-02 Three roles | `role` ENUM on the users table | Phases 1, 9 | Inspect the schema; create a user of each role | Complete |
| RF-03 Protected areas | Session check at the top of each protected page | Phase 9 | Request a protected page while logged out; confirm the redirect | Complete |
| RF-04 URL submission | Dashboard form | Phase 9 | Submit a URL | Complete |
| RF-05 Crawl and store | `python/crawler_v4.py` | Phase 3 | Confirm a new raw page row containing the HTML | Complete |
| RF-06 AI field extraction | `scripts/openai_parse_raw_pages.php` | Phase 4 | Confirm a listings row with the expected fields | Complete |
| RF-07 Weighted SQL scoring | `scripts/find_duplicates.php` | Phase 6 | Run against seeded duplicates; compare with expected scores | Complete |
| RF-08 AI description comparison | `scripts/ai_compare_descriptions.php` | Phase 6 | Rewrite a description and confirm it is still matched | Complete |
| RF-09 Ranked results | Dashboard results table | Phase 9 | Submit a URL and read the table | Complete |
| RF-10 Search tracking | `search_usage` table | Phases 1, 9 | Run two searches; confirm the monthly counter increments | Complete |
| RF-11 Admin panel | Admin dashboard, AI listings viewer, raw viewer, crawler log | Phase 9 | Perform each CRUD operation; view the live log | Complete |
| RF-12 EN/ES interface | `public/js/lang.js` | Phase 9 | Toggle language on each page | Complete |
| RF-13 AI chat widget | `public/chat.php`, chat widget partial | Phase 9 | Ask the widget a question about the project | Complete |
| RF-14 Legal pages | `public/privacy.php`, `public/legal.php` | Phase 11 | Open both pages and check the required content | Complete |
| RNF Security | PDO parameterised queries, bcrypt, session auth | Throughout | Review query construction and password storage | Complete |
| RNF Performance | 120-second limit; AI comparison capped at the top 5 candidates | Phase 10 | Time a full run; confirm the cap in the dashboard code | Complete |
| RNF Cost | Tokens per run | Phase 10 | Measure tokens per run — 2,000–5,000 | Complete |
| RNF Data protection | Minimum data collected; processing documented | Phase 11 | Review the users table and the Privacy Policy | Complete |

Every stated requirement is implemented and verifiable. What the matrix does not claim is that the product is complete as a commercial proposition — the exclusions listed in 3.4, and particularly the absence of true publication dates, are deliberate limitations of scope rather than unmet requirements.

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

**Has the initially identified need been resolved?** Partly, and it is worth being precise about which part. The need had two components: identifying that several advertisements describe the same property, and determining which one came first. The first is solved — the system reliably groups duplicate listings, including cases where the description has been rewritten, which was the hardest version of the problem. The second is not fully solved, because the system orders by when the crawler first encountered a listing rather than by genuine publication date, and the portals do not reliably expose the latter. The tool therefore delivers real value today as a duplicate detector, and the "which came first" claim is currently limited by data availability rather than by the implementation.

**Have the objectives been achieved?** Yes. All twelve objectives set out in 3.4 are implemented and verifiable on the delivered system, and both fixed deadlines were met — a working prototype demonstrated live at the presentation on 27 February, and the finished system delivered on 25 March.

**What final result was obtained?** A working web application with a four-step pipeline from URL to ranked duplicate list, three user roles, a user dashboard, an administration panel with full CRUD and a live crawler log, a bilingual interface, an AI chat widget, GDPR and LSSI legal pages, and a documented public repository. It runs on a local stack and is designed to be deployable to a normal server environment.

**What were the main difficulties?** Three stand out. The **crawler** was the largest single consumer of effort — five versions before one worked reliably, because different sites have different structures, different amounts of raw text and different JSON-LD implementations. The **parser** was the hardest design problem: extracting structured fields from arbitrary HTML in pure code proved at least very complicated, and only worked properly once I moved to the OpenAI API. And **cost control** turned out to be a real engineering constraint rather than an afterthought — at 15,000–20,000 tokens per run the system worked but would not have been sellable, and reducing that to 2,000–5,000 took deliberate effort.

**What have I learned?** Technically, how to integrate a language model into a pipeline as an ordinary component and — more usefully — when *not* to use it. The two-stage design, filtering cheaply in SQL before spending anything on AI, is the most transferable idea in the project. I also learned that removing technology is often the right decision rather than a retreat: dropping ChromaDB simplified the system and improved accuracy at the same time.

On process, I learned that building the front-end last was correct, and that testing every function in isolation and then integrated is what kept a fragile system stable across nine major component rewrites. The clearest weakness I would fix is my commit messages, which are not descriptive enough to serve as the change record I have relied on them being.

**What improvements or extensions could be made?** In rough order of value:

- **Genuine publication dates** — this would convert the product from a duplicate detector into what it actually claims to be, and is the single highest-value extension.
- **More portals** — the main scaling problem and the main source of fragility.
- **Image-based matching** — deliberately excluded here; it would catch listings whose text has been completely rewritten.
- **Real deployment** — a production server with firewall, automated backups and monitoring.
- **Billing integration** — the `subscriptions` table exists, but no payment flow was built.
- **Validation with real users** — the largest gap in the evaluation, and the first thing I would do if the project continued.

**Overall.** The project set out to demonstrate everything learned during the course by building something well beyond a simple example, and it does: server-side programming in two languages, persistent storage, a full CRUD administration panel, client-side work, internationalisation, an external API integration, legal compliance and version control, all applied to a real problem I encountered in a real job. It is a working product with honest, documented limitations rather than a finished commercial platform — and for the time available, I think that is the right outcome.

------------------------------------------------------------------------

# 7. Bibliography and sources consulted

Include the sources you have actually used during the project: technical documentation, books, articles, regulations, manuals, documentation for languages, frameworks, APIs, libraries or services.

Use a consistent format across all references.

Do not include sources that have not been consulted, and distinguish, where applicable, between bibliography, technical documentation and regulations.

**ANSWER**

**Technical documentation**

- OpenAI. *API documentation* — models, chat completions and token usage. Consulted throughout parser, comparator and chat widget development.
- The PHP Group. *PHP manual* — in particular PDO, sessions and password hashing.
- Python Software Foundation. *Python 3 documentation* — standard library, HTTP requests and HTML parsing.
- Oracle / MariaDB Foundation. *MySQL / MariaDB reference manual* — schema definition, joins and conditional expressions.
- The Apache Software Foundation. *Apache HTTP Server documentation* — local configuration via Homebrew.
- Git and GitHub documentation — version control and repository hosting.
- Schema.org — JSON-LD structured data vocabulary, consulted while designing the crawler's extraction logic.

**Regulations**

- Regulation (EU) 2016/679, General Data Protection Regulation (GDPR) — reviewed to confirm the application's data handling is compliant; Art. 6.1.b used as the legal basis for processing.
- Ley 34/2002, de Servicios de la Sociedad de la Información y de Comercio Electrónico (LSSI-CE) — basis for the Legal Notice published on the site.

**Project sources**

- Sorensen, O. *FirstListing* — project repository. https://github.com/oscarsorensen/FirstListing
- Sorensen, O. *Proyecto Intermodular I* — first-year project report on FirstListing. Source of the sector analysis, business figures and development timeline reused in this document.

**Sector sources**

- Direct professional experience in a real estate agency on the Costa Blanca — origin of the problem statement and source of the estimate of 850–900 active agencies in Costa Blanca South.
- Idealista and Fotocasa — examined as the closest comparable products in the sector.

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
| 15 | All financial figures — 24,000 € salary, ~7,500 € materials, ~31,500 € total, 88 break-even clients, 20 €/h, 375 h | Carried over unchanged from the Intermodular report for consistency. Confirm I still stand behind them. |
| 16 | "All 14 functional requirements delivered" (5.2) and the full traceability matrix (5.7) | **I invented the RF-01…RF-14 requirement IDs for this report** — they did not exist before. Walk through the list once against the running application and confirm every row is genuinely complete before claiming it in writing. |
| 17 | Developer and user manuals (4.8) | The Intermodular report described these as planned. Confirm whether they now exist; if not, either write them or change the wording from a description to future work. |

## D. Decisions only I can make

| # | Question |
|---|---|
| 18 | **Is this DAW final project the same FirstListing project, or a continuation of it with new work?** I have written the entire report as FirstListing, retrospectively, using first-year dates. If the second-year project is meant to extend it, then 3.3, 3.4, 4.1, 4.6 and 6 need new scope, new dates and new objectives. **This is the most important question on the list — everything else is detail by comparison.** |
| 19 | **Tense and framing.** The template is written as though the project is about to start; I have written retrospectively because it is finished. Confirm the assessor accepts that, since it affects almost every section. |
| 20 | **Whether to delete the guidance text**, the `-- PUT THE ANSWER ... HERE` markers and the `**ANSWER**` labels before submitting. I left all of it in place as asked. |
| 21 | **Whether the report should be in Spanish.** I have written in English to match the source material and the `.EN` filename, but the module and the original template are in Spanish. |