const SNACKS = [
    {
        id: 1,
        store_id: 1,
        category_id: 1,
        name: "Glico Pocky Strawberry Biscuit Sticks (Family Pack)",
        condition_grade: "dented_box",
        condition_label: "Dented Packaging",
        expiry_date: "2026-11-27",
        price: 14000,
        original_value: 38000,
        discount_percent: 63,
        description: "Crispy biscuit sticks coated in sweet strawberry cream. Outer carton box has a squished top-right corner from pallet transport.",
        defect_notes: "Outer cardboard box has a noticeable crease/dent on the top corner. The internal silver foil packet is 100% airtight, factory sealed, and crisp.",
        image_url: "frontend/assets/img/snacks/pocky_strawberry_dented.jpg",
        available_qty: 6,
        category_name: "Biscuits & Cookies",
        store_name: "Circle K – University Campus Hub",
        district: "Thu Duc City"
    },
    {
        id: 2,
        store_id: 2,
        category_id: 1,
        name: "Oreo Vanilla Creme Cookies (Pack of 3)",
        condition_grade: "crumbled_biscuits",
        condition_label: "Crumbled / Broken",
        expiry_date: "2026-12-04",
        price: 12000,
        original_value: 32000,
        discount_percent: 63,
        description: "Classic chocolate sandwich cookies with smooth vanilla creme. Several cookies were cracked into pieces during shipping.",
        defect_notes: "Approximately 40% of the cookies inside are broken or crumbled. Still 100% fresh, crunchy, and perfect for dipping into milk.",
        image_url: "frontend/assets/img/snacks/oreo_crumbled_cookies.jpg",
        available_qty: 12,
        category_name: "Biscuits & Cookies",
        store_name: "FamilyMart – Student District 1",
        district: "District 1"
    },
    {
        id: 3,
        store_id: 3,
        category_id: 2,
        name: "Nestle KitKat Chunky Milk Chocolate (Box of 4)",
        condition_grade: "melted_reset",
        condition_label: "Melted & Re-set",
        expiry_date: "2026-12-08",
        price: 18000,
        original_value: 48000,
        discount_percent: 63,
        description: "Crispy wafer finger covered in thick milk chocolate. Melted and re-solidified in air-conditioned storage.",
        defect_notes: "Chocolate coating shows light cocoa bloom and uneven surface after melting and re-setting in cool air. Crisp wafer texture inside is completely unaffected.",
        image_url: "frontend/assets/img/snacks/kitkat_melted_set.jpg",
        available_qty: 6,
        category_name: "Chocolates & Sweets",
        store_name: "Tokyo Snack Corner – Binh Thanh Hub",
        district: "Binh Thanh District"
    },
    {
        id: 4,
        store_id: 1,
        category_id: 3,
        name: "Lay's Magic Masala Potato Chips (Buddy Pack)",
        condition_grade: "dented_box",
        condition_label: "Dented Packaging",
        expiry_date: "2026-11-23",
        price: 15000,
        original_value: 35000,
        discount_percent: 57,
        description: "Crunchy potato crisps with savory seasoning. Outer foil bag has cosmetic creases on the upper seam.",
        defect_notes: "Outer foil bag has cosmetic creases and slight crinkle on the top seam. Nitrogen cushion seal is 100% intact, chips are perfectly crispy.",
        image_url: "frontend/assets/img/snacks/lays_magic_masala.jpg",
        available_qty: 5,
        category_name: "Chips & Savory Snacks",
        store_name: "Circle K – University Campus Hub",
        district: "Thu Duc City"
    },
    {
        id: 5,
        store_id: 3,
        category_id: 2,
        name: "Meiji Meltykiss Creamy Milk Chocolate (Box of 5)",
        condition_grade: "melted_reset",
        condition_label: "Melted & Re-set",
        expiry_date: "2026-11-30",
        price: 25000,
        original_value: 65000,
        discount_percent: 62,
        description: "Premium Japanese cocoa-dusted melt-in-mouth truffles. Misshapen cubes from temperature fluctuation.",
        defect_notes: "Meltykiss winter-limited edition truffles. Cubes slightly softened in transit and re-set; decadent melt-in-mouth cocoa richness 100% preserved.",
        image_url: "frontend/assets/img/snacks/meltykiss_box.jpg",
        available_qty: 8,
        category_name: "Chocolates & Sweets",
        store_name: "Tokyo Snack Corner – Binh Thanh Hub",
        district: "Binh Thanh District"
    },
    {
        id: 6,
        store_id: 2,
        category_id: 5,
        name: "Lotte Choco Pie Original Marshmallow (Rich Pack)",
        condition_grade: "dented_box",
        condition_label: "Dented Packaging",
        expiry_date: "2026-11-25",
        price: 19000,
        original_value: 45000,
        discount_percent: 58,
        description: "Two soft cake layers filled with fluffy marshmallow, dipped in chocolate. Exterior carton has squished side.",
        defect_notes: "Exterior display box was squashed from the side during stocking. The individual airtight plastic wraps around each choco pie are 100% factory pristine.",
        image_url: "frontend/assets/img/snacks/choco_pie_dented.jpg",
        available_qty: 7,
        category_name: "Packaged Breads & Buns",
        store_name: "FamilyMart – Student District 1",
        district: "District 1"
    },
    {
        id: 7,
        store_id: 1,
        category_id: 4,
        name: "Lotte Pepero Almond Crunch (Special Box)",
        condition_grade: "near_expiry",
        condition_label: "Near-Expiry (7-30d)",
        expiry_date: "2026-11-19",
        price: 16000,
        original_value: 42000,
        discount_percent: 62,
        description: "Crunchy biscuit sticks dipped in rich chocolate and crushed roasted almonds. Approaching best-before date.",
        defect_notes: "Only 9 days remaining until printed best-before date. 100% crispy, perfectly sealed, taste tested by store staff.",
        image_url: "frontend/assets/img/snacks/pepero_almond_dented.jpg",
        available_qty: 9,
        category_name: "Imported Asian Snacks",
        store_name: "Circle K – University Campus Hub",
        district: "Thu Duc City"
    },
    {
        id: 8,
        store_id: 3,
        category_id: 1,
        name: "Hokkaido Cream Cheese Wafer Sticks (Party Box)",
        condition_grade: "crumbled_biscuits",
        condition_label: "Crumbled / Broken",
        expiry_date: "2026-12-01",
        price: 22000,
        original_value: 62000,
        discount_percent: 65,
        description: "Fragrant wafer sticks filled with rich Hokkaido cheese cream. Ends of wafer sticks cracked in transit.",
        defect_notes: "Fragile light wafer sticks; approximately 25% of the tips are cracked inside. Crispiness and fragrant cheese cream are delicious.",
        image_url: "frontend/assets/img/snacks/hokkaido_cheese_wafers.jpg",
        available_qty: 4,
        category_name: "Biscuits & Cookies",
        store_name: "Tokyo Snack Corner – Binh Thanh Hub",
        district: "Binh Thanh District"
    },
    {
        id: 9,
        store_id: 2,
        category_id: 3,
        name: "TaoKaeNoi Crispy Seaweed Rolls (Bulgogi BBQ, 6s)",
        condition_grade: "near_expiry",
        condition_label: "Near-Expiry (7-30d)",
        expiry_date: "2026-11-18",
        price: 18000,
        original_value: 50000,
        discount_percent: 64,
        description: "Super crispy roasted seaweed sheets seasoned with savory sweet Korean bulgogi glaze. 8 days before date.",
        defect_notes: "Approaching printed date in 8 days. Stored in climate-controlled shelving; individual rolls are sealed airtight and crunchy.",
        image_url: "frontend/assets/img/snacks/seaweed_bulgogi_pack.jpg",
        available_qty: 11,
        category_name: "Chips & Savory Snacks",
        store_name: "FamilyMart – Student District 1",
        district: "District 1"
    },
    {
        id: 10,
        store_id: 1,
        category_id: 3,
        name: "Nongshim Crispy Ramen Noodle Snack (Family Size)",
        condition_grade: "crumbled_biscuits",
        condition_label: "Crumbled / Broken",
        expiry_date: "2026-11-26",
        price: 11000,
        original_value: 28000,
        discount_percent: 61,
        description: "Crunchy crushed dry ramen noodle snack with BBQ flavor powder. Crushed into small bits, ideal for snacking.",
        defect_notes: "The ramen cake was broken into bite-sized pieces inside the packet. Actually much easier to eat without breaking manually!",
        image_url: "frontend/assets/img/snacks/nongshim_crispy_noodles.jpg",
        available_qty: 15,
        category_name: "Chips & Savory Snacks",
        store_name: "Circle K – University Campus Hub",
        district: "Thu Duc City"
    }
];

module.exports = (req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    if (req.method === 'OPTIONS') {
        return res.status(200).end();
    }

    const { id, grade, cat, q, store_id } = req.query;

    // Single item lookup
    if (id) {
        const snack = SNACKS.find(s => s.id === parseInt(id));
        if (!snack) {
            return res.status(404).json({ success: false, message: 'Snack not found' });
        }
        return res.status(200).json({ success: true, snack });
    }

    // Filter list
    let filtered = [...SNACKS];

    if (grade && grade !== 'all') {
        filtered = filtered.filter(s => s.condition_grade === grade);
    }

    if (cat && cat !== 'all') {
        filtered = filtered.filter(s => s.category_id === parseInt(cat));
    }

    if (store_id && store_id !== 'all') {
        filtered = filtered.filter(s => s.store_id === parseInt(store_id));
    }

    if (q) {
        const query = q.toLowerCase().trim();
        filtered = filtered.filter(s =>
            s.name.toLowerCase().includes(query) ||
            s.description.toLowerCase().includes(query) ||
            s.category_name.toLowerCase().includes(query)
        );
    }

    return res.status(200).json({
        success: true,
        count: filtered.length,
        total: SNACKS.length,
        snacks: filtered
    });
};
