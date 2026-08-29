<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $rows = [
            // ── Services ──────────────────────────────────────
            [
                'intent'   => 'service-info',
                'category' => 'service',
                'title'    => 'Ground Express',
                'content'  => 'Fast and reliable ground deliveries tailored to shipping needs. Secure transport for any size or destination, including bulk, time-sensitive, and specialized-handling shipments. Serves Automotive, Electronics, FMCG, Heavy Engineering, Textile/Apparel, Pharma, and Household industries.',
                'keywords' => 'ground express,surface,truck delivery',
            ],
            [
                'intent'   => 'service-info',
                'category' => 'service',
                'title'    => 'Air Express',
                'content'  => '24-hour Air Express service for urgent, time-sensitive shipments, connected to 34 commercial airports nationwide, with direct air delivery in 8 metro cities (Delhi, Mumbai, Ahmedabad, Pune, Kolkata, Hyderabad, Bengaluru, Chennai). Offered via third-party airline carrier partners; Allcargo is not an aircraft carrier. Serves Automotive, Electronics, FMCG, Heavy Engineering, Textile/Apparel, Pharma.',
                'keywords' => 'air express,urgent shipment,fast delivery,air cargo',
            ],
            [
                'intent'   => 'service-info',
                'category' => 'service',
                'title'    => 'Retail Services',
                'content'  => 'Efficient inventory management, fast replenishments, and seamless distribution for retail businesses, including handling of seasonal demand and shelf-stocking logistics. Also includes a dedicated student relocation service.',
                'keywords' => 'retail,inventory,replenishment,student relocation',
            ],
            [
                'intent'   => 'service-info',
                'category' => 'service',
                'title'    => 'Consultative Logistics',
                'content'  => 'End-to-end, tailored logistics solutions covering Warehousing & Storage, Distribution & Inbound Logistics, Transportation Management, Store & Line Feed, In-plant Management, and Ecommerce Order Fulfillment. Industries served: Chemical, Automotive, Retail & Fashion, Electronics, E-commerce. Chemical-industry logistics ensures safe, compliant, efficient inventory management from planning to delivery.',
                'keywords' => 'warehousing,distribution,transportation management,in-plant,ecommerce fulfillment,chemical logistics',
            ],
            [
                'intent'   => 'service-info',
                'category' => 'service',
                'title'    => 'Sustainability',
                'content'  => 'Allcargo has saved 25,310 tons of CO2 emissions through reduced transportation distance, improved vehicle efficiency, low-emission vehicles, smart packaging, and e-dockets. Over 1,700 rebranded vehicles and 500+ alternate-fuel vehicles are in use for first/last-mile delivery.',
                'keywords' => 'sustainability,green,co2,emissions,electric vehicle',
            ],

            // ── Self-service tools ───────────────────────────
            [
                'intent'   => 'shipment-tracking',
                'category' => 'tool',
                'title'    => 'Track Shipment',
                'content'  => 'Track a shipment in real time using the Docket number, Reference number, or Mobile number at https://www.allcargologistics.com/track-shipment',
                'keywords' => 'track,tracking,docket,shipment status,where is my shipment',
            ],
            [
                'intent'   => 'rate-estimate',
                'category' => 'tool',
                'title'    => 'Get Estimate',
                'content'  => 'Get a shipping rate estimate/quote before booking a shipment via the "Get Estimate" tool on the Allcargo Logistics website.',
                'keywords' => 'estimate,quote,rate,price,cost,how much',
            ],
            [
                'intent'   => 'rate-estimate',
                'category' => 'tool',
                'title'    => 'Tariff',
                'content'  => 'Published per-kg tariff rates are available at https://www.allcargologistics.com/tariff. Note: tariff for Srinagar, Leh & Port Blair is ₹150/kg due to special destination surcharge.',
                'keywords' => 'tariff,rate per kg,pricing,srinagar,leh,port blair',
            ],
            [
                'intent'   => 'rate-estimate',
                'category' => 'tool',
                'title'    => 'Convert Weight & Volume',
                'content'  => 'Tool to convert shipment weight and volume for accurate rate calculation.',
                'keywords' => 'weight,volume,convert,volumetric weight',
            ],
            [
                'intent'   => 'pickup-request',
                'category' => 'tool',
                'title'    => 'Book Pickup / Book Shipment',
                'content'  => 'Book a shipment pickup online at https://www.allcargologistics.com/book-a-shipment. Requires an Allcargo Logistics account login; new customers can sign up first. If a pickup request fails, verify shipment details and try again, or contact customer support.',
                'keywords' => 'book pickup,book shipment,schedule pickup,collection',
            ],
            [
                'intent'   => 'branch-locator',
                'category' => 'tool',
                'title'    => 'Branch Locator',
                'content'  => 'Find the nearest Allcargo Logistics branch or office using the Branch Locator tool on the website.',
                'keywords' => 'branch,nearest office,location,near me',
            ],
            [
                'intent'   => 'pincode-check',
                'category' => 'tool',
                'title'    => 'Find a Serviceable Pincode',
                'content'  => 'Check whether a given pincode is serviceable by Allcargo Logistics using the Pincode Enquiry tool on the website.',
                'keywords' => 'pincode,pin code,serviceable,do you deliver to',
            ],
            [
                'intent'   => 'complaint-tracking',
                'category' => 'tool',
                'title'    => 'Claim Request',
                'content'  => 'File a claim for a shipment issue (damage, loss, delay) using the Claim Request tool on the website.',
                'keywords' => 'claim,damage,loss,file a claim',
            ],
            [
                'intent'   => 'complaint-tracking',
                'category' => 'tool',
                'title'    => 'Track Complaint or Claim',
                'content'  => 'Track the status of a previously filed complaint or claim by entering the Claim/Complaint number on the website.',
                'keywords' => 'track complaint,complaint status,claim status,ticket status',
            ],

            // ── FAQ topics ────────────────────────────────────
            [
                'intent'   => 'faq',
                'category' => 'faq',
                'title'    => 'Shipper Risk / Owner Risk',
                'content'  => 'Shipper Risk / Owner Risk refers to who bears the risk of loss or damage to goods during transit under the shipment terms. Customers should confirm the specific risk terms at the time of booking.',
                'keywords' => 'shipper risk,owner risk,liability,insurance,damage responsibility',
            ],
            [
                'intent'   => 'faq',
                'category' => 'faq',
                'title'    => 'NVOCC (Non-Vessel Owning Common Carrier)',
                'content'  => 'NVOCC FAQs cover the principal functions of a Non-Vessel Owning Common Carrier in ocean freight consolidation. See https://www.allcargologistics.com/nvocc-frequently-asked-questions for details.',
                'keywords' => 'nvocc,ocean freight,consolidation,carrier',
            ],
            [
                'intent'   => 'faq',
                'category' => 'faq',
                'title'    => 'CFS (Container Freight Station)',
                'content'  => 'CFS FAQs cover Container Freight Station processes. See https://www.allcargologistics.com/cfs-frequently-asked-questions for details.',
                'keywords' => 'cfs,container freight station',
            ],
            [
                'intent'   => 'faq',
                'category' => 'faq',
                'title'    => 'FCL (Full Container Load)',
                'content'  => 'FCL FAQs cover Full Container Load shipping. See https://www.allcargologistics.com/fcl-frequently-asked-questions for details.',
                'keywords' => 'fcl,full container load,container shipping',
            ],

            // ── Contact / support ─────────────────────────────
            [
                'intent'   => null, // always injected as a safety net by the service
                'category' => 'contact',
                'title'    => 'Support Channels',
                'content'  => 'Helpline (toll-free): 1860-123-4284. Email: customerservices@allcargologistics.com. Website: https://www.allcargologistics.com/. Contact page: https://www.allcargologistics.com/contact-us.',
                'keywords' => 'contact,helpline,phone,email,support',
            ],
            [
                'intent'   => 'service-info',
                'category' => 'company',
                'title'    => 'About Allcargo Logistics',
                'content'  => 'Allcargo Logistics has over three decades of expertise in express and consultative logistics, with an extensive network of 71+ distribution centers and transshipment hubs across India, offering real-time shipment visibility through advanced tracking, online portals, and mobile apps.',
                'keywords' => 'about,company,who are you,history',
            ],
        ];

        foreach ($rows as &$row) {
            $row['language']   = 'en';
            $row['is_active']  = 1;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        $this->db->table('knowledge_base')->insertBatch($rows);
    }
}
