module.exports = (req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    if (req.method === 'OPTIONS') {
        return res.status(200).end();
    }

    return res.status(200).json({
        status: 'online',
        service: 'NomReNom Backend API',
        host: 'Vercel Serverless Functions',
        version: '1.0.0-MVP',
        environment: process.env.VERCEL_ENV || 'production',
        region: process.env.VERCEL_REGION || 'global-edge',
        endpoints: {
            health: '/api/health',
            snacks: '/api/snacks',
            stores: '/api/stores',
            orders: '/api/orders',
            auth: '/api/auth'
        },
        timestamp: new Date().toISOString()
    });
};
