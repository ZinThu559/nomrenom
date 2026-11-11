const ADMINS = {
    'admin.alex@nomrenom.vn': { pass: 'NomAdmin2026!Alex', name: 'Alex Nguyen', role: 'admin', title: 'Lead Operations Admin' },
    'admin.linh@nomrenom.vn': { pass: 'NomAdmin2026!Linh', name: 'Linh Tran', role: 'admin', title: 'Store Inventory Admin' },
    'admin.minh@nomrenom.vn': { pass: 'NomAdmin2026!Minh', name: 'Minh Vo', role: 'admin', title: 'Quality & Refunds Admin' },
    'admin@nomrenom.vn': { pass: 'Admin@12345', name: 'Master Admin', role: 'admin', title: 'Platform Superadmin' }
};

const USERS = {};

module.exports = (req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    if (req.method === 'OPTIONS') {
        return res.status(200).end();
    }

    if (req.method !== 'POST') {
        return res.status(405).json({ success: false, message: 'Use POST for authentication' });
    }

    try {
        const body = typeof req.body === 'string' ? JSON.parse(req.body) : (req.body || {});
        const { action = 'login', email, password, name, phone, preferredHub } = body;

        const normalizedEmail = (email || '').toLowerCase().trim();

        if (action === 'register') {
            if (!name || !normalizedEmail || !password) {
                return res.status(400).json({ success: false, message: 'Name, email, and password are required' });
            }

            if (password.length < 6) {
                return res.status(400).json({ success: false, message: 'Password must be at least 6 characters' });
            }

            const newUser = {
                id: `usr_${Date.now()}`,
                name: name.trim(),
                email: normalizedEmail,
                phone: phone || '',
                preferredHub: preferredHub || 'Circle K – University Campus Hub',
                role: 'student',
                created_at: new Date().toISOString()
            };

            USERS[normalizedEmail] = { ...newUser, password };

            return res.status(201).json({
                success: true,
                message: 'Account created successfully! Welcome to NomReNom.',
                user: newUser
            });
        }

        if (action === 'login') {
            if (!normalizedEmail || !password) {
                return res.status(400).json({ success: false, message: 'Please provide both email and password' });
            }

            // Check admin accounts
            if (ADMINS[normalizedEmail]) {
                const admin = ADMINS[normalizedEmail];
                if (admin.pass !== password) {
                    return res.status(401).json({ success: false, message: 'Invalid administrator password' });
                }
                return res.status(200).json({
                    success: true,
                    message: `Welcome back, ${admin.name}!`,
                    user: {
                        name: admin.name,
                        email: normalizedEmail,
                        role: 'admin',
                        title: admin.title
                    }
                });
            }

            // Check registered customer accounts
            if (USERS[normalizedEmail]) {
                const customer = USERS[normalizedEmail];
                if (customer.password !== password) {
                    return res.status(401).json({ success: false, message: 'Incorrect password for this email' });
                }
                const { password: _, ...safeUser } = customer;
                return res.status(200).json({
                    success: true,
                    message: `Welcome back, ${customer.name}!`,
                    user: safeUser
                });
            }

            // Standard student auto-login
            const namePart = normalizedEmail.split('@')[0];
            const capitalized = namePart.charAt(0).toUpperCase() + namePart.slice(1);

            return res.status(200).json({
                success: true,
                message: `Welcome, ${capitalized}!`,
                user: {
                    name: capitalized,
                    email: normalizedEmail,
                    role: 'student'
                }
            });
        }

        return res.status(400).json({ success: false, message: `Unknown action: ${action}` });
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
};
