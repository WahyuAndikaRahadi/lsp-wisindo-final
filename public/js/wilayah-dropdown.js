/**
 * Reusable Wilayah Indonesia Dynamic Dropdown (Provinsi -> Kota/Kabupaten)
 * Works with local API (/api/wilayah) and public fallback (emsifa).
 */
window.wilayahDropdown = function (config = {}) {
    return {
        provinces: [],
        cities: [],
        loadingProvinces: false,
        loadingCities: false,
        selectedProvCode: String(config.initialProvCode || ''),
        selectedProvName: config.initialProvName || '',
        selectedCityCode: String(config.initialCityCode || ''),
        selectedCityName: config.initialCityName || '',

        async init() {
            await this.loadProvinces();
            if (this.selectedProvCode) {
                await this.loadCities(this.selectedProvCode, true);
            }
        },

        async loadProvinces() {
            this.loadingProvinces = true;
            try {
                let res = await fetch('/api/wilayah/provinces');
                if (!res.ok) {
                    res = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
                }
                const data = await res.json();
                this.provinces = (Array.isArray(data) ? data : []).map(p => ({
                    code: String(p.code || p.id),
                    name: p.name
                }));

                // Match province by name if code is missing but name exists
                if (!this.selectedProvCode && this.selectedProvName) {
                    const normProv = this.selectedProvName.trim().toLowerCase();
                    const match = this.provinces.find(p => 
                        p.name.toLowerCase() === normProv || 
                        normProv.includes(p.name.toLowerCase()) || 
                        p.name.toLowerCase().includes(normProv)
                    );
                    if (match) {
                        this.selectedProvCode = match.code;
                        this.selectedProvName = match.name;
                    }
                } else if (this.selectedProvCode) {
                    const cleanCode = this.selectedProvCode.replace(/\D/g, '');
                    const match = this.provinces.find(p => 
                        p.code.replace(/\D/g, '') === cleanCode
                    );
                    if (match && !this.selectedProvName) {
                        this.selectedProvName = match.name;
                    }
                }
            } catch (err) {
                console.error('Gagal memuat API provinsi:', err);
            } finally {
                this.loadingProvinces = false;
            }
        },

        async onProvinceChange() {
            const cleanCode = this.selectedProvCode.replace(/\D/g, '');
            const found = this.provinces.find(p => p.code.replace(/\D/g, '') === cleanCode);
            this.selectedProvName = found ? found.name : '';
            this.selectedCityCode = '';
            this.selectedCityName = '';
            this.cities = [];

            if (this.selectedProvCode) {
                await this.loadCities(this.selectedProvCode, false);
            }
        },

        async loadCities(provCode, isInitial = false) {
            if (!provCode) return;
            this.loadingCities = true;
            const cleanProv = provCode.replace(/\D/g, '');
            try {
                let res = await fetch(`/api/wilayah/regencies/${cleanProv}`);
                if (!res.ok) {
                    res = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${cleanProv}.json`);
                }
                const data = await res.json();
                this.cities = (Array.isArray(data) ? data : []).map(c => ({
                    code: String(c.code || c.id),
                    name: c.name
                }));

                // Pre-select city if initial load
                if (isInitial) {
                    if (this.selectedCityCode) {
                        const cleanCity = this.selectedCityCode.replace(/\D/g, '');
                        const match = this.cities.find(c => c.code.replace(/\D/g, '') === cleanCity);
                        if (match) {
                            this.selectedCityCode = match.code;
                            if (!this.selectedCityName) {
                                this.selectedCityName = match.name;
                            }
                        }
                    } else if (this.selectedCityName) {
                        const target = this.selectedCityName.trim().toLowerCase();
                        const cleanTarget = target.replace(/^(kabupaten|kota|kab\.?)\s+/i, '');
                        // 1. Try exact
                        let match = this.cities.find(c => c.name.toLowerCase() === target);
                        // 2. Try normalized
                        if (!match) {
                            match = this.cities.find(c => {
                                const cClean = c.name.toLowerCase().replace(/^(kabupaten|kota|kab\.?)\s+/i, '');
                                return cClean === cleanTarget;
                            });
                        }
                        if (match) {
                            this.selectedCityCode = match.code;
                            this.selectedCityName = match.name;
                        }
                    }
                }
            } catch (err) {
                console.error('Gagal memuat API kota/kabupaten:', err);
            } finally {
                this.loadingCities = false;
            }
        },

        onCityChange() {
            const cleanCode = this.selectedCityCode.replace(/\D/g, '');
            const found = this.cities.find(c => c.code.replace(/\D/g, '') === cleanCode);
            this.selectedCityName = found ? found.name : '';
        }
    };
};
