const STORES = [
    {
        id: 1,
        name: "Circle K – University Campus Hub",
        slug: "circle-k-campus-hub",
        description: "Partner convenience store located directly across university dorms. Provides express counter pickup for near-expiry and dented packaged snacks.",
        address: "Khu pho 6, Linh Trung Ward, Thu Duc City (Opposite Dorm A)",
        district: "Thu Duc City",
        city: "Ho Chi Minh City",
        phone: "028 3724 5566",
        email: "circlek.dorm@nomrenom.vn",
        opening_time: "07:00",
        closing_time: "23:00",
        image_url: "frontend/assets/img/bakeries/bakery-store-1.jpg"
    },
    {
        id: 2,
        name: "FamilyMart – Student District 1",
        slug: "familymart-d1",
        description: "Downtown student-friendly convenience mart stocking near-expiry Japanese treats, dented cookie packs, and discounted imported snacks.",
        address: "88 Nguyen Thi Minh Khai, Ben Nghe Ward, District 1",
        district: "District 1",
        city: "Ho Chi Minh City",
        phone: "028 3822 1199",
        email: "familymart.d1@nomrenom.vn",
        opening_time: "06:30",
        closing_time: "23:30",
        image_url: "frontend/assets/img/bakeries/bakery-store-2.jpg"
    },
    {
        id: 3,
        name: "Tokyo Snack Corner – Binh Thanh Hub",
        slug: "tokyo-snack-binh-thanh",
        description: "Specialty importer store for Asian confectionery. Stocks melted-and-set chocolates, cracked wafer biscuits, and dented canister snacks.",
        address: "420 Dien Bien Phu, Ward 25, Binh Thanh District (near HUTECH & UEF)",
        district: "Binh Thanh District",
        city: "Ho Chi Minh City",
        phone: "028 3512 8877",
        email: "tokyosnack.bt@nomrenom.vn",
        opening_time: "08:00",
        closing_time: "22:00",
        image_url: "frontend/assets/img/bakeries/bakery-store-3.jpg"
    }
];

module.exports = (req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    if (req.method === 'OPTIONS') {
        return res.status(200).end();
    }

    const { id } = req.query;
    if (id) {
        const store = STORES.find(s => s.id === parseInt(id));
        if (!store) {
            return res.status(404).json({ success: false, message: 'Store not found' });
        }
        return res.status(200).json({ success: true, store });
    }

    return res.status(200).json({
        success: true,
        count: STORES.length,
        stores: STORES
    });
};
