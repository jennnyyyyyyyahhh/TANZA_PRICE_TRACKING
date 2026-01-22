/* Price update notifier
   - Polls /php/fetch_price_updates.php
   - Shows one side popup per product when its modal price or latest timestamp changes
   - Stores seen snapshot in localStorage key 'ppm_price_update_seen_v1'
   - For testing: open browser console and run `localStorage.removeItem('ppm_price_update_seen_v1')` then reload
*/
(function(){
    const ENDPOINT = '/php/fetch_price_updates.php';
    const STORAGE_KEY = 'ppm_price_update_seen_v1';
    const POLL_INTERVAL_MS = 15000;

    function safeParse(v){ try { return JSON.parse(v); } catch(e){ return null; } }

    function getSeen(){
        const raw = localStorage.getItem(STORAGE_KEY);
        const parsed = safeParse(raw);
        if (!parsed || typeof parsed !== 'object') return {initialized:false, items:{}};
        return parsed;
    }

    function setSeen(obj){
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(obj)); } catch(e) { console.warn('Failed save seen', e); }
    }

    function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m])); }

    function timeAgo(ts){
        try{
            const then = new Date(ts);
            const diff = Date.now() - then.getTime();
            const sec = Math.floor(diff/1000);
            if (sec < 60) return `${sec}s ago`;
            const min = Math.floor(sec/60);
            if (min < 60) return `${min}m ago`;
            const hr = Math.floor(min/60);
            if (hr < 24) return `${hr}h ago`;
            const days = Math.floor(hr/24);
            return `${days}d ago`;
        }catch(e){ return '' }
    }

    function showPopup(item){
        const holderId = 'ppm-price-update-holder';
        let holder = document.getElementById(holderId);
        if (!holder){
            holder = document.createElement('div');
            holder.id = holderId;
            holder.className = 'ppm-price-update-holder';
            holder.setAttribute('data-ppm-portal','true');
            // append to the documentElement (html) to avoid being affected by transformed ancestors
            try {
                document.documentElement.appendChild(holder);
                console.debug('ppm: appended holder to document.documentElement');
            } catch(e) {
                document.body.appendChild(holder);
                console.debug('ppm: appended holder to body (fallback)');
            }
        }
        // enforce inline styles every time so page CSS won't accidentally reposition the holder
        holder.style.setProperty('position','fixed','important');
        holder.style.setProperty('left','50%','important');
        holder.style.setProperty('right','auto','important');
        holder.style.setProperty('top','18px','important');
        holder.style.setProperty('bottom','auto','important');
        holder.style.setProperty('transform','translateX(-50%)','important');
        holder.style.setProperty('z-index','2147483647','important');
        holder.style.setProperty('display','flex','important');
        holder.style.setProperty('flex-direction','column','important');
        holder.style.setProperty('align-items','center','important');
        holder.style.setProperty('gap','12px','important');
        holder.style.setProperty('pointer-events','none','important');
        holder.style.setProperty('max-width','calc(100% - 36px)','important');

        const container = document.createElement('div');
        container.className = 'ppm-price-update-popup ppm-slide-in';
        const commodityText = item.commodity_type ? `<div class="ppm-popup-commodity" style="font-size:13px;color:#0b6b3b;margin-bottom:6px">${escapeHtml(item.commodity_type)}</div>` : '';
        container.innerHTML = `
            <div class="ppm-popup-body">
                <div class="ppm-popup-left">₱</div>
                <div class="ppm-popup-main">
                    <div class="ppm-popup-title">Price update</div>
                    <div class="ppm-popup-product">${escapeHtml(item.product_name)}</div>
                    ${commodityText}
                    <div class="ppm-popup-text">New modal price: <strong>₱${escapeHtml(item.price)}</strong></div>
                    <div class="ppm-popup-meta">Based on ${item.count} survey(s) • <span class="ppm-popup-time">${timeAgo(item.latest_created_at)}</span></div>
                </div>
                <button class="ppm-popup-close" aria-label="close">×</button>
            </div>
        `;
        container.querySelector('.ppm-popup-close').addEventListener('click', ()=>{
            container.classList.remove('ppm-slide-in');
            container.classList.add('ppm-slide-out');
            setTimeout(()=> container.remove(), 350);
        });

        // insert newest at the top so it's immediately visible in the top-center
        if (holder.firstChild) holder.insertBefore(container, holder.firstChild); else holder.appendChild(container);
        setTimeout(()=>{
            if (container.parentNode){ container.classList.remove('ppm-slide-in'); container.classList.add('ppm-slide-out'); setTimeout(()=> container.remove(),350); }
        }, 5000);
    }

    async function fetchData(){
        try{
            const res = await fetch(ENDPOINT, {cache:'no-store'});
            if (!res.ok) { console.warn('price updates fetch failed', res.status); return null; }
            const j = await res.json();
            return j && j.data ? j.data : [];
        } catch(e){ console.warn('fetch error', e); return null; }
    }

    async function check(){
        const data = await fetchData();
        if (!data) return;
        const seen = getSeen();

        // initialize on first run (do not notify historic entries)
        if (!seen.initialized){
            const snap = { initialized: true, items: {} };
            data.forEach(d => { snap.items[d.product_name] = { price: d.price, latest: d.latest_created_at, count: d.count }; });
            setSeen(snap);
            console.log('ppm: snapshot initialized');
            return;
        }

        const updated = [];
        data.forEach(d => {
            const key = d.product_name;
            const prev = seen.items[key];
            if (!prev){
                // new product -> notify once
                updated.push(d);
                seen.items[key] = { price: d.price, latest: d.latest_created_at, count: d.count };
            } else {
                // notify when price changed OR latest timestamp changed OR count increased
                if (+prev.price !== +d.price || prev.latest !== d.latest_created_at || (typeof prev.count === 'number' && d.count > prev.count)){
                    updated.push(d);
                    seen.items[key] = { price: d.price, latest: d.latest_created_at, count: d.count };
                }
            }
        });

        if (updated.length){
            updated.forEach(it => showPopup(it));
            setSeen(seen);
        }
    }

    // start
    check();
    setInterval(check, POLL_INTERVAL_MS);

    // Debug helpers (call from console):
    // window.ppmPriceUpdate.clearSeen() -> clears local snapshot
    // window.ppmPriceUpdate.runNow() -> runs a single check
    // window.ppmPriceUpdate.forceShow() -> fetches and immediately shows popups for all current modal prices (useful for testing)
    window.ppmPriceUpdate = {
        clearSeen: function(){ try{ localStorage.removeItem(STORAGE_KEY); console.log('ppm: cleared seen snapshot'); }catch(e){console.warn(e);} },
        runNow: function(){ check().then(()=>console.log('ppm: runNow complete')).catch(e=>console.warn(e)); },
        forceShow: async function(){
            const data = await fetchData();
            if (!data) { console.warn('ppm: no data'); return; }
            data.forEach(d => showPopup(d));
            console.log('ppm: forceShow displayed', data.length, 'items');
        }
    };

})();
