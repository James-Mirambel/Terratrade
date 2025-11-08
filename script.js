/* script.js - TerraTrade prototype with role-based behaviors */

/* helpers */
const $ = sel => document.querySelector(sel);
const $$ = sel => Array.from(document.querySelectorAll(sel));
const fmt = n => "₱" + Number(n).toLocaleString();

/* ===== MODAL MANAGEMENT SYSTEM ===== */
const ModalManager = {
  activeModal: null,
  modalStack: [],
  overlayClickToClose: true,
  escapeKeyToClose: true,
  
  // Initialize modal system
  init() {
    this.setupGlobalEventListeners();
    this.registerAllModals();
    console.log('Modal Manager initialized');
  },
  
  // Setup global event listeners
  setupGlobalEventListeners() {
    // ESC key to close modals
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.escapeKeyToClose && this.activeModal) {
        this.closeModal(this.activeModal);
      }
    });
    
    // Click outside modal to close
    document.addEventListener('click', (e) => {
      if (this.overlayClickToClose && e.target.classList.contains('modal')) {
        this.closeModal(e.target.id);
      }
    });
    
    // Prevent modal content clicks from closing modal
    $$('.modal-content').forEach(content => {
      content.addEventListener('click', (e) => {
        e.stopPropagation();
      });
    });
  },
  
  // Register all modals in the application
  registerAllModals() {
    const modals = [
      'authModal',
      'listingModal', 
      'landSellingModal',
      'messagingModal',
      'newConversationModal',
      'offerModal',
      'counterModal',
      'auctionModal',
      'escrowModal',
      'adminModal',
      'myListingsModal',
      'uploadReportModal',
      'confirmModal',
      'alertModal',
      'loadingModal',
      'offerIdentificationModal'
    ];
    
    modals.forEach(modalId => {
      this.setupModalCloseButtons(modalId);
    });
  },
  
  // Setup close buttons for a modal
  setupModalCloseButtons(modalId) {
    const modal = $(`#${modalId}`);
    if (!modal) return;
    
    // Find and setup close buttons
    const closeButtons = modal.querySelectorAll('.modal-close, .modal-cancel, [data-close="modal"]');
    closeButtons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.closeModal(modalId);
      });
    });
  },
  
  // Open a modal
  openModal(modalId, options = {}) {
    const modal = $(`#${modalId}`);
    if (!modal) {
      console.warn(`Modal ${modalId} not found`);
      return false;
    }
    
    // Close current modal if replacing
    if (this.activeModal && !options.stack) {
      this.closeModal(this.activeModal, true);
    }
    
    // Stack modal if requested
    if (options.stack && this.activeModal) {
      this.modalStack.push(this.activeModal);
    }
    
    // Show modal
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    
    // Update active modal
    this.activeModal = modalId;
    
    // Add body class to prevent scrolling
    document.body.classList.add('modal-open');
    
    // Focus management
    this.manageFocus(modal);
    
    // Call onOpen callback if provided
    if (options.onOpen) {
      options.onOpen(modal);
    }
    
    // Trigger custom event
    this.triggerEvent('modalOpened', { modalId, modal });
    
    return true;
  },
  
  // Close a modal
  closeModal(modalId, silent = false) {
    const modal = typeof modalId === 'string' ? $(`#${modalId}`) : modalId;
    const id = typeof modalId === 'string' ? modalId : modalId.id;
    
    if (!modal) return false;
    
    // Hide modal
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    
    // Clear form data if it's a form modal
    this.clearModalForm(modal);
    
    // Update active modal
    if (this.activeModal === id) {
      this.activeModal = null;
    }
    
    // Handle modal stack
    if (this.modalStack.length > 0) {
      const previousModal = this.modalStack.pop();
      this.activeModal = previousModal;
    } else {
      // Remove body class if no more modals
      document.body.classList.remove('modal-open');
    }
    
    // Trigger custom event
    if (!silent) {
      this.triggerEvent('modalClosed', { modalId: id, modal });
    }
    
    return true;
  },
  
  // Close all modals
  closeAllModals() {
    const openModals = $$('.modal:not(.hidden)');
    openModals.forEach(modal => {
      this.closeModal(modal.id, true);
    });
    this.activeModal = null;
    this.modalStack = [];
    document.body.classList.remove('modal-open');
  },
  
  // Check if a modal is open
  isModalOpen(modalId) {
    const modal = $(`#${modalId}`);
    return modal && !modal.classList.contains('hidden');
  },
  
  // Get current active modal
  getActiveModal() {
    return this.activeModal;
  },
  
  // Clear form data in modal
  clearModalForm(modal) {
    const inputs = modal.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
      if (input.type === 'checkbox' || input.type === 'radio') {
        input.checked = false;
      } else if (input.type === 'file') {
        input.value = '';
      } else {
        input.value = '';
      }
    });
    
    // Clear selected states
    modal.querySelectorAll('.selected').forEach(el => {
      el.classList.remove('selected');
    });
  },
  
  // Focus management
  manageFocus(modal) {
    // Find the first focusable element
    const focusableElements = modal.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements.length > 0) {
      focusableElements[0].focus();
    }
  },
  
  // Trigger custom events
  triggerEvent(eventName, detail) {
    const event = new CustomEvent(eventName, { detail });
    document.dispatchEvent(event);
  },
  
  // Show loading modal
  showLoading(message = 'Loading...') {
    this.createLoadingModal(message);
    this.openModal('loadingModal');
  },
  
  // Hide loading modal
  hideLoading() {
    this.closeModal('loadingModal');
  },
  
  // Show confirmation modal
  showConfirm(options = {}) {
    const {
      title = 'Confirm',
      message = 'Are you sure?',
      confirmText = 'Yes',
      cancelText = 'Cancel',
      onConfirm = () => {},
      onCancel = () => {}
    } = options;
    
    this.createConfirmModal(title, message, confirmText, cancelText, onConfirm, onCancel);
    this.openModal('confirmModal');
  },
  
  // Show alert modal
  showAlert(options = {}) {
    const {
      title = 'Alert',
      message = '',
      buttonText = 'OK',
      onClose = () => {}
    } = options;
    
    this.createAlertModal(title, message, buttonText, onClose);
    this.openModal('alertModal');
  },
  
  // Create loading modal dynamically
  createLoadingModal(message) {
    let modal = $('#loadingModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'loadingModal';
      modal.className = 'modal hidden';
      modal.innerHTML = `
        <div class="modal-content loading-modal">
          <div class="loading-spinner"></div>
          <p id="loadingMessage">${message}</p>
        </div>
      `;
      document.body.appendChild(modal);
    } else {
      $('#loadingMessage').textContent = message;
    }
  },
  
  // Create confirm modal dynamically
  createConfirmModal(title, message, confirmText, cancelText, onConfirm, onCancel) {
    let modal = $('#confirmModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'confirmModal';
      modal.className = 'modal hidden';
      document.body.appendChild(modal);
    }
    
    modal.innerHTML = `
      <div class="modal-content confirm-modal">
        <h3>${title}</h3>
        <p>${message}</p>
        <div class="modal-actions">
          <button id="confirmCancel" class="btn ghost">${cancelText}</button>
          <button id="confirmOK" class="btn primary">${confirmText}</button>
        </div>
      </div>
    `;
    
    // Setup event handlers
    $('#confirmOK').addEventListener('click', () => {
      this.closeModal('confirmModal');
      onConfirm();
    });
    
    $('#confirmCancel').addEventListener('click', () => {
      this.closeModal('confirmModal');
      onCancel();
    });
  },
  
  // Create alert modal dynamically
  createAlertModal(title, message, buttonText, onClose) {
    let modal = $('#alertModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'alertModal';
      modal.className = 'modal hidden';
      document.body.appendChild(modal);
    }
    
    modal.innerHTML = `
      <div class="modal-content alert-modal">
        <h3>${title}</h3>
        <p>${message}</p>
        <div class="modal-actions">
          <button id="alertOK" class="btn primary">${buttonText}</button>
        </div>
      </div>
    `;
    
    // Setup event handler
    $('#alertOK').addEventListener('click', () => {
      this.closeModal('alertModal');
      onClose();
    });
  }
};

const state = {
  user: null,                 // { email, name, role, kyc, brokerCompliance, ... }
  listings: [],
  favorites: new Set(),
  savedSearches: [],
  notifications: [],
  currentListing: null,
  currentOffer: null,
  currentAuction: null,
  users: [],                  // for admin demo: list of registered users
  offers: [],                 // all offers across all listings
  auctionBids: [],            // all auction bids
  contracts: [],              // PSA contracts and e-signatures
  escrowAccounts: [],         // escrow accounts and transactions
  titleVerifications: [],     // title verification records
  closingChecklists: [],      // closing process tracking
  reviews: [],                // reviews and ratings
  offerIdCounter: 1,          // offer ID counter
  contractIdCounter: 1,       // contract ID counter
  escrowIdCounter: 1          // escrow ID counter
};

/* demo listings */
const demo = [
  { 
    id:1001, 
    title:"1200sqm Residential Lot", 
    price:2500000, 
    zoning:"Residential", 
    area:1200, 
    region:"Region VII", 
    province:"Cebu", 
    city:"Lapu-Lapu", 
    barangay:"Mactan", 
    coords:"10.307,123.961", 
    desc:"Near airport. Paved access. Power available.", 
    thumbnails:[], 
    type:"sale", 
    owner:"owner1@demo.ph", 
    reports:[], 
    offers:[], 
    bids:[],
    escrow: null,
    title_status: "pending",
    closing_checklist: [],
    reviews: []
  },
  { 
    id:1002, 
    title:"5000sqm Agricultural Land", 
    price:1800000, 
    zoning:"Agricultural", 
    area:5000, 
    region:"Region X", 
    province:"Bukidnon", 
    city:"Valencia", 
    barangay:"Mailag", 
    coords:"8.049,125.095", 
    desc:"Fertile soil. Irrigation nearby.", 
    thumbnails:[], 
    type:"sale", 
    owner:"farmer@demo.ph", 
    reports:[], 
    offers:[], 
    bids:[],
    escrow: null,
    title_status: "verified",
    closing_checklist: [],
    reviews: []
  },
  { 
    id:1003, 
    title:"Corner Commercial Lot (450sqm)", 
    price:4200000, 
    zoning:"Commercial", 
    area:450, 
    region:"NCR", 
    province:"Metro Manila", 
    city:"Quezon City", 
    barangay:"Kamuning", 
    coords:"14.638,121.043", 
    desc:"High foot traffic. Ideal for retail.", 
    thumbnails:[], 
    type:"auction", 
    auctionEnds: Date.now() + (1000*60*10), 
    auctionMode: "open", // "open", "sealed", "timed"
    reservePrice: 3500000,
    bidIncrement: 50000,
    antiSnipingExtension: 300000, // 5 minutes in ms
    owner:"broker@demo.ph", 
    reports:[], 
    offers:[], 
    bids:[],
    escrow: null,
    title_status: "verified",
    closing_checklist: [],
    reviews: []
  }
];

/* Local storage */
function loadState(){
  const s = localStorage.getItem("lts_state_v2");
  if (s){
    try {
      const p = JSON.parse(s);
      state.favorites = new Set(p.favorites || []);
      state.savedSearches = p.savedSearches || [];
      state.user = p.user || null;
      state.users = p.users || [];
    } catch(e){ console.warn("load parse err", e); }
  }
}
function saveState(){
  localStorage.setItem("lts_state_v2", JSON.stringify({
    favorites: Array.from(state.favorites),
    savedSearches: state.savedSearches,
    user: state.user,
    users: state.users
  }));
}

/* notifications */
function notify(text){
  const id = Date.now();
  state.notifications.unshift({ id, text, time: new Date().toLocaleString() });
  renderNotifications();
  $("#notifBadge").classList.remove("hidden");
  $("#notifBadge").textContent = state.notifications.length;
}

/* init */
function init(){
  loadState();
  // seed listings if empty
  state.listings = demo.slice();
  // ensure demo admin exists
  /*
  if (!state.users.find(u=>u.email==="admin@demo.ph")){
    state.users.push({ email:"admin@demo.ph", name:"Admin Demo", role:"admin", kyc:true, brokerCompliance:false });
  }
  */
  $("#year").textContent = new Date().getFullYear();
  renderSavedSearches();
  renderListings();
  updateAuthArea();
  renderNotifications();
  setupHandlers();
  
  // Initialize Modal Manager
  ModalManager.init();
  
  saveState();
}
init();

/* Role helpers - All users have all roles */
function isGuest(){ return !state.user; }
function isBuyer(){ return true; }  // All users can act as buyers
function isSeller(){ return true; } // All users can act as sellers
function isBroker(){ return true; } // All users can act as brokers
function isAdmin(){ return state.user && state.user.role === 'admin'; } // Only admin users

/* Listing card template (string build safe) */
function listingCardHTML(l){
  const fav = state.favorites.has(l.id) ? '♥' : '♡';
  const auctionBadge = l.type === "auction" ? '<span class="badge">Auction</span>' : '';
  const ownerLabel = `<small class="muted">owner: ${l.owner}</small>`;
  return `<div class="card" data-id="${l.id}">
    <div class="thumb">${auctionBadge}<div class="badges"><span class="badge">${l.zoning}</span></div></div>
    <h4>${l.title}</h4>
    <div class="meta">${l.region} • ${l.city}</div>
    <div class="info-row">
      <div class="info">${fmt(l.price)}</div>
      <div>
        <button class="btn view-btn" data-id="${l.id}">View</button>
        <button class="btn fav-btn" data-id="${l.id}">${fav}</button>
      </div>
    </div>
  </div>`;
}

/* filters */
function applyFilters(list){
  const q = ($("#filterSearch")?.value || $("#searchInput")?.value || "").trim().toLowerCase();
  const region = ($("#filterRegion")?.value || "").trim().toLowerCase();
  const zoning = $("#filterZoning")?.value;
  const pmin = Number($("#filterPriceMin")?.value || 0);
  const pmax = Number($("#filterPriceMax")?.value || 0);
  const minA = Number($("#filterMinArea")?.value || 0);
  return list.filter(l=>{
    if (q && !( (l.title+" "+l.city+" "+l.province+" "+l.region).toLowerCase().includes(q) )) return false;
    if (region && !l.region.toLowerCase().includes(region)) return false;
    if (zoning && zoning !== "" && l.zoning !== zoning && !(zoning==="Auction" && l.type==="auction")) return false;
    if (pmin && l.price < pmin) return false;
    if (pmax && l.price > pmax) return false;
    if (minA && l.area < minA) return false;
    return true;
  });
}

/* render listings */
function renderListings(){
  const filtered = applyFilters(state.listings);
  $("#resultCount").textContent = `${filtered.length} result${filtered.length===1?"":"s"}`;
  $("#listingsGrid").innerHTML = filtered.map(listingCardHTML).join("");
  // attach events
  $$(".view-btn").forEach(b => b.addEventListener("click", e => openListing(Number(e.target.dataset.id))));
  $$(".fav-btn").forEach(b => b.addEventListener("click", e => toggleFav(Number(e.target.dataset.id))));
}

/* favorites */
function toggleFav(id){
  if (state.favorites.has(id)) state.favorites.delete(id);
  else state.favorites.add(id);
  saveState();
  renderListings();
  notify(`Listing ${id} ${state.favorites.has(id) ? "added to favorites" : "removed from favorites"}`);
}

/* saved searches - functionality removed */
function renderSavedSearches() {
  // No-op function to prevent errors - search functionality removed
}

/* notifications */
function renderNotifications(){
  const list = $("#notificationsList");
  if (!list) return;
  list.innerHTML = state.notifications.length ? state.notifications.map(n=>`<li><small class="muted">${n.time}</small><div>${n.text}</div></li>`).join("") : "<li class='muted'>No notifications</li>";
  $("#notifBadge").textContent = state.notifications.length || 0;
  if (state.notifications.length) $("#notifBadge").classList.remove("hidden"); else $("#notifBadge").classList.add("hidden");
}

/* event handlers */
function setupHandlers(){
  // search & apply
  $("#searchBtn")?.addEventListener("click", renderListings);
  $("#searchInput")?.addEventListener("keydown", e => e.key === "Enter" && renderListings());
  $("#applyFilters")?.addEventListener("click", ()=>{ renderListings(); if (window.innerWidth < 980) toggleSidebar(false); });
  $("#clearFilters")?.addEventListener("click", ()=>{
    $("#filterSearch").value = ""; $("#filterRegion").value = ""; $("#filterZoning").value = ""; $("#filterPriceMin").value = ""; $("#filterPriceMax").value = ""; $("#filterMinArea").value = "";
    renderListings();
  });
  // remove reference to saveCurrentSearch which no longer exists
  // $("#saveSearch")?.addEventListener("click", saveCurrentSearch);

  // header buttons
  $("#filtersToggle")?.addEventListener("click", ()=> toggleSidebar());
  // Removed message button handler - moved to DOMContentLoaded to ensure button exists
  $("#notificationsBtn")?.addEventListener("click", ()=> $("#notificationsPanel").classList.toggle("hidden"));
  $("#closeNotifs")?.addEventListener("click", ()=> $("#notificationsPanel").classList.add("hidden"));

  // auth modal open
  document.addEventListener("click", (e)=>{
    if (e.target && e.target.id === "loginBtn") ModalManager.openModal("authModal");
  });

  // auth modal handlers
  $("#authClose")?.addEventListener("click", ()=> ModalManager.closeModal("authModal"));
  $("#loginTab")?.addEventListener("click", ()=> switchAuthPanel("login"));
  $("#registerTab")?.addEventListener("click", ()=> switchAuthPanel("register"));

  $("#authRole")?.addEventListener("change", (e)=>{
    const r = e.target.value;
    if (r === "broker") { $("#brokerComplianceRow").classList.remove("hidden"); }
    else $("#brokerComplianceRow").classList.add("hidden");
  });

  $("#authSubmit")?.addEventListener("click", authLogin);
  $("#authRegister")?.addEventListener("click", authRegister);

  // sell / listings - updated to use new land selling modal
  $("#sellBtn")?.addEventListener("click", ()=> {
    if (!state.user) { switchAuthPanel("login"); notify("Please login/register to create listings."); return; }
    openLandSellingModal();
  });

  // quick actions visibility
  $("#myListingsBtn")?.addEventListener("click", ()=> openMyListings());
  
  // buyer-specific actions
  $("#myOffersBtn")?.addEventListener("click", ()=> openMyOffers());
  $("#myContractsBtn")?.addEventListener("click", ()=> openMyContracts());
  
  // seller/broker actions for viewing offers
  $("#viewOffersBtn")?.addEventListener("click", ()=> openReceivedOffers());
  
  // make offer button removed from header - only exists in listing modal now

  // listing modal
  $("#listingClose")?.addEventListener("click", ()=> ModalManager.closeModal("listingModal"));
  $("#favBtn")?.addEventListener("click", ()=> { if (!state.currentListing) return; toggleFav(state.currentListing.id); });
  $("#makeBidBtn")?.addEventListener("click", ()=> openIdentificationModal('bid'));
  $("#makeOfferBtn")?.addEventListener("click", ()=> openIdentificationModal('offer'));

  $("#offerClose")?.addEventListener("click", ()=> ModalManager.closeModal("offerModal"));
  $("#submitOffer")?.addEventListener("click", submitOffer);

  $("#acceptCounter")?.addEventListener("click", ()=> { ModalManager.closeModal("counterModal"); notify("You accepted the counter-offer. Proceed to escrow."); openEscrow(state.currentOffer.amount); });
  $("#rejectCounter")?.addEventListener("click", ()=> { ModalManager.closeModal("counterModal"); notify("You rejected the counter-offer."); });

  // escrow buttons
  $("#escrowClose")?.addEventListener("click", ()=> ModalManager.closeModal("escrowModal"));
  $("#depositFunds")?.addEventListener("click", ()=> {
    if (!state.currentOffer) return ModalManager.showAlert({ title: 'Error', message: 'No offer in context.' });
    $("#escrowStatus").textContent = `Buyer deposited ${fmt(state.currentOffer.amount)} into escrow.`;
    $("#depositFunds").classList.add("hidden");
    $("#releaseFunds").classList.remove("hidden");
    notify("Buyer deposited funds to escrow.");
  });
  $("#releaseFunds")?.addEventListener("click", ()=> {
    $("#escrowStatus").textContent = "Funds released to seller. Transaction complete.";
    $("#releaseFunds").disabled = true;
    notify("Funds released to seller.");
  });

  // auction
  $("#auctionClose")?.addEventListener("click", ()=> ModalManager.closeModal("auctionModal"));
  $("#placeBid")?.addEventListener("click", ()=> {
    const bid = Number($("#bidAmount")?.value || 0);
    if (!bid) return ModalManager.showAlert({ title: 'Invalid Bid', message: 'Please enter a bid amount.' });
    if (!state.currentAuction) return;
    if (bid <= state.currentAuction.topBid) return ModalManager.showAlert({ title: 'Invalid Bid', message: 'Bid must be higher than current top bid.' });
    state.currentAuction.topBid = bid;
    state.currentAuction.topBidder = state.user ? state.user.email : "guest";
    $("#auctionInfo").innerHTML = `<div>Top bid: ${fmt(state.currentAuction.topBid)} by ${state.currentAuction.topBidder}</div><div>Ends in: <span id="auctionTimer"></span></div>`;
    notify(`New bid ${fmt(bid)} on ${state.currentAuction.title}`);
  });

}

/* sidebar toggle */
function toggleSidebar(force){
  const sb = $("#sidebarFilters");
  if (typeof force === "boolean") sb.classList.toggle("hidden", !force);
  else sb.classList.toggle("hidden");
}

/* ===== Auth flows ===== */
function switchAuthPanel(which){
  if (which === "login"){
    $("#loginTab").classList.add("active"); $("#registerTab").classList.remove("active");
    $("#loginPanel").classList.remove("hidden"); $("#registerPanel").classList.add("hidden");
    $("#loginTab").setAttribute("aria-selected","true"); $("#registerTab").setAttribute("aria-selected","false");
  } else {
    $("#registerTab").classList.add("active"); $("#loginTab").classList.remove("active");
    $("#registerPanel").classList.remove("hidden"); $("#loginPanel").classList.add("hidden");
    $("#registerTab").setAttribute("aria-selected","true"); $("#loginTab").setAttribute("aria-selected","false");
  }
  $("#authModal").classList.remove("hidden");
}
function closeAuthModal(){ $("#authModal").classList.add("hidden"); }

function authLogin(){
  const email = $("#authEmail")?.value.trim();
  const pw = $("#authPassword")?.value || "demo";
  if (!email || !pw) return alert("Provide email & password.");
  // try to find user in demo users, otherwise auto-create a buyer for simplicity
  let user = state.users.find(u=>u.email===email);
  if (!user){
    user = { email, name: email.split("@")[0], role: "buyer", kyc: false, brokerCompliance:false };
    state.users.push(user);
  }
  state.user = user;
  saveState();
  updateAuthArea();
  closeAuthModal();
  notify(`Logged in as ${email}`);
}

/* register */
function authRegister(){
  const name = $("#authName")?.value.trim();
  const email = $("#authEmailR")?.value.trim();
  const pw = $("#authPasswordR")?.value || "demo";
  const kycFile = $("#authKYC")?.files?.[0];
  const brokerFile = $("#brokerAttest")?.files?.[0];

  if (!name || !email || !pw) return alert("Complete the form.");
  // store small user object - default role is "buyer" but all functionality is available
  const user = { email, name, role: "buyer", kyc: !!kycFile, brokerCompliance: !!brokerFile, signedDocs: [] };
  state.user = user;
  state.users.push(user);
  saveState();
  updateAuthArea();
  closeAuthModal();
  notify(`Registered: ${email}`);
}

/* update auth area in header */
function updateAuthArea(){
  const area = $("#authArea");
  if (!state.user){
    area.innerHTML = `<button id="loginBtn" class="btn primary">Login</button>`;
    $("#loginBtn")?.addEventListener("click", ()=> $("#authModal").classList.remove("hidden"));
    // hide role-specific buttons and messages
    $("#myListingsBtn")?.classList.add("hidden");
    $("#uploadReportBtn")?.classList.add("hidden");
    $("#adminDashboardBtn")?.classList.add("hidden");
    $("#messagesBtn")?.classList.add("hidden");
    return;
  }
  const role = state.user.role;
  area.innerHTML = `<span class="small" style="font-weight: 500; color: #1f2937;">Hi, ${state.user.email} ${state.user.kyc ? "✅" : "⚠️"}</span>
    <button id="logoutBtn" class="btn ghost">Logout</button>`;
  $("#logoutBtn")?.addEventListener("click", ()=> { state.user = null; saveState(); updateAuthArea(); notify("Logged out"); renderListings(); });

  // show messages button for logged in users
  $("#messagesBtn")?.classList.remove("hidden");
  
  // Modified: Show all quick action buttons for all users
  // All users can now access all functionalities
  $("#myListingsBtn")?.classList.remove("hidden");
  
  // show/hide sell button - all users can now sell land
  $("#sellBtn")?.classList.remove("hidden");
  
  // show/hide buttons for viewing received offers - all users can now view offers
  $("#viewOffersBtn")?.classList.remove("hidden");
  
  // hide View Bids button for all users (removed from system)
  $("#viewBidsBtn")?.classList.add("hidden");
  
  // hide Make Offer button from header - it should only appear in listing view
  $("#makeOfferBtn")?.classList.add("hidden");
  
  // Modified: Show all buttons for all users
  $("#myOffersBtn")?.classList.remove("hidden");
  $("#myContractsBtn")?.classList.remove("hidden");
  
  // hide My Bids button for all users (redundant with My Offers)
  $("#myBidsBtn")?.classList.add("hidden");
  
  // All users can now access seller/broker functionalities
  $("#mySellerOffersBtn")?.classList.remove("hidden");
  $("#mySellerContractsBtn")?.classList.remove("hidden");
}

/* require KYC helper */
function requireKYC(cb){
  if (!state.user){ switchAuthPanel("login"); notify("Please login/register and complete KYC to proceed."); return; }
  if (!state.user.kyc){ alert("Please complete KYC to perform this action."); switchAuthPanel("register"); return; }
  // Removed role-based restrictions - all users can access all functionality
  cb();
}

/* Sell wizard (simple prompt flow) */
function openSellWizard(){
  const title = prompt("Listing title (e.g., 1200sqm residential lot):");
  if (!title) return;
  const price = Number(prompt("Asking price (PHP):", "1000000"));
  if (!price) return alert("Price required.");
  const zoning = prompt("Zoning (Residential/Agricultural/Commercial/Industrial):","Residential");
  const region = prompt("Region:","Region VII");
  const listing = {
    id: Date.now(),
    title, price, zoning,
    area: Number(prompt("Area (sqm):","1000")),
    region, province: prompt("Province:",""), city:prompt("City:"), barangay:prompt("Barangay:"), desc:prompt("Short description:",""),
    thumbnails: [], type: prompt("Type (sale/auction):","sale"), owner: state.user.email, reports:[]
  };
  if (listing.type === "auction") listing.auctionEnds = Date.now() + (1000*60*5);
  state.listings.unshift(listing);
  renderListings();
  notify(`Listing published (pending review): ${listing.title}`);
}

/* Open listing details */
function openListing(id){
  const l = state.listings.find(x=>x.id===id);
  if (!l) return;
  state.currentListing = l;

  $("#ldTitle").textContent = l.title;
  $("#ldBadges").innerHTML = `<span class="badge">${l.zoning}</span> ${l.type==="auction" ? '<span class="badge">Auction</span>' : ''}`;

  $("#ldDesc").textContent = l.desc;
  $("#ldZoning").textContent = l.zoning;
  $("#ldArea").textContent = (l.area||"—") + " sqm";
  $("#ldPrice").textContent = fmt(l.price);
  $("#ldRegion").textContent = `${l.region} / ${l.city}`;
  $("#mapBox").textContent = `[Map: ${l.coords || 'n/a'}]`;
  $("#favBtn").textContent = state.favorites.has(l.id) ? "♥ Favorited" : "♡ Favorite";

  // Initialize offers array if not exists
  if (!l.offers) l.offers = [];
  if (!l.bids) l.bids = [];

  // role-based controls for listing owner (seller/broker)
  const userEmail = state.user ? state.user.email : null;
  const isOwner = state.user && (isSeller() && userEmail === l.owner || isBroker() && userEmail === l.owner);
  
  if (isOwner){
    // Owner sees management buttons
    $("#acceptOfferBtn").classList.remove("hidden");
    $("#counterOfferBtn").classList.remove("hidden");
    $("#signDocBtn").classList.remove("hidden");
    $("#makeOfferBtn").classList.add("hidden");
    $("#makeBidBtn").classList.add("hidden");
  } else {
    // Hide owner buttons
    $("#acceptOfferBtn").classList.add("hidden");
    $("#counterOfferBtn").classList.add("hidden");
    $("#signDocBtn").classList.add("hidden");
    
    // Show offer/bid buttons for all logged-in users
    if (state.user) {
      if (l.type === "auction") {
        $("#makeOfferBtn").classList.add("hidden");
        $("#makeBidBtn").classList.remove("hidden");
      } else {
        $("#makeOfferBtn").classList.remove("hidden");
        $("#makeBidBtn").classList.add("hidden");
      }
    } else {
      // Hide both for guests
      $("#makeOfferBtn").classList.add("hidden");
      $("#makeBidBtn").classList.add("hidden");
    }
  }

  // auction button
  if (l.type === "auction") { $("#openAuctionBtn").classList.remove("hidden"); $("#openAuctionBtn").onclick = ()=> openAuction(l.id); } else $("#openAuctionBtn").classList.add("hidden");

  // Show existing offers/bids summary
  displayOffersBidsSummary(l);

  // listing reports
  const reportsBox = $("#listingReports");
  reportsBox.innerHTML = '';
  if (l.reports && l.reports.length){
    reportsBox.innerHTML = '<strong>Reports & plans</strong><ul>' + l.reports.map(r=>`<li>${r.name} <small class="muted">(${r.uploader})</small></li>`).join('') + '</ul>';
  }

  // wire owner actions
  $("#acceptOfferBtn").onclick = ()=> { /* owner accepts current offer (if any) */ if (!state.currentOffer) return alert("No current offer to accept."); state.currentOffer.status = "accepted"; notify(`Seller accepted offer ${fmt(state.currentOffer.amount)}. Proceed to escrow.`); openEscrow(state.currentOffer.amount); };
  $("#counterOfferBtn").onclick = ()=> { const amt = Number(prompt("Counter amount (PHP):", state.currentOffer ? state.currentOffer.amount : l.price)); if (!amt) return; state.currentOffer = state.currentOffer || { listingId: l.id }; state.currentOffer.counter = amt; $("#counterText").textContent = `Seller proposes ${fmt(amt)} (counter-offer).`; $("#counterModal").classList.remove("hidden"); notify(`Seller made a counter-offer: ${fmt(amt)}`); };
  $("#signDocBtn").onclick = ()=> { signDocumentForListing(l.id); };

  // Directly attach event listener to message button
  console.log('Attempting to attach event listener to message button');
  const messageBtn = $("#messageSellerBtn");
  console.log('Message button found:', !!messageBtn);
  if (messageBtn) {
    // Remove any existing event listeners
    const newBtn = messageBtn.cloneNode(true);
    messageBtn.parentNode.replaceChild(newBtn, messageBtn);
    
    // Add new event listener
    newBtn.addEventListener('click', (e) => {
      e.preventDefault();
      console.log('Direct message button click handler triggered');
      MessageSystem.openMessagingFromListing();
    });
    console.log('Event listener attached to message button');
  } else {
    console.log('ERROR: Message button not found when trying to attach event listener');
  }

  $("#listingModal").classList.remove("hidden");
}

/* ===== COMPREHENSIVE OFFER SYSTEM ===== */

// Open enhanced offer modal
function openEnhancedOfferModal() {
  if (!state.currentListing) return;
  
  // Populate offer form with listing details
  $('#enhancedOfferPrice').value = state.currentListing.price;
  $('#enhancedOfferEarnestMoney').value = Math.round(state.currentListing.price * 0.05); // Default 5%
  
  // Set default closing date (45 days from now)
  const closingDate = new Date();
  closingDate.setDate(closingDate.getDate() + 45);
  $('#enhancedOfferClosingDate').value = closingDate.toISOString().split('T')[0];
  
  ModalManager.openModal('enhancedOfferModal');
}

// Submit comprehensive offer
function submitEnhancedOffer() {
  if (!state.currentListing || !state.user) return;
  
  const formData = {
    price: Number($('#enhancedOfferPrice').value || 0),
    earnestMoney: Number($('#enhancedOfferEarnestMoney').value || 0),
    financingContingency: $('#financingContingency').checked,
    financingDays: Number($('#financingDays').value || 30),
    surveyContingency: $('#surveyContingency').checked,
    surveyDays: Number($('#surveyDays').value || 10),
    titleContingency: $('#titleContingency').checked,
    titleDays: Number($('#titleDays').value || 15),
    environmentalContingency: $('#environmentalContingency').checked,
    environmentalDays: Number($('#environmentalDays').value || 20),
    closingDate: $('#enhancedOfferClosingDate').value,
    inclusions: $('#offerInclusions').value.trim(),
    exclusions: $('#offerExclusions').value.trim(),
    specialTerms: $('#specialTerms').value.trim(),
    buyerComments: $('#buyerComments').value.trim()
  };
  
  // Validation
  if (!formData.price) {
    alert('Please enter an offer price.');
    return;
  }
  
  if (!formData.earnestMoney) {
    alert('Please enter earnest money amount.');
    return;
  }
  
  if (!formData.closingDate) {
    alert('Please select a closing date.');
    return;
  }
  
  // Create comprehensive offer
  const offer = {
    id: state.offerIdCounter++,
    listingId: state.currentListing.id,
    version: 1,
    buyer: state.user.email,
    buyerName: state.user.name,
    status: 'submitted',
    submittedAt: Date.now(),
    ...formData,
    contingencies: {
      financing: formData.financingContingency ? { enabled: true, days: formData.financingDays } : { enabled: false },
      survey: formData.surveyContingency ? { enabled: true, days: formData.surveyDays } : { enabled: false },
      title: formData.titleContingency ? { enabled: true, days: formData.titleDays } : { enabled: false },
      environmental: formData.environmentalContingency ? { enabled: true, days: formData.environmentalDays } : { enabled: false }
    },
    history: [{
      action: 'submitted',
      timestamp: Date.now(),
      actor: state.user.email,
      details: `Offer submitted for ${fmt(formData.price)}`
    }]
  };
  
  // Add to listing's offers array
  state.currentListing.offers.push(offer);
  
  // Add to global offers array
  state.offers.push(offer);
  
  // Set as current offer
  state.currentOffer = offer;
  
  // Close modal and notify
  ModalManager.closeModal('enhancedOfferModal');
  notify(`Comprehensive offer of ${fmt(formData.price)} submitted for ${state.currentListing.title}`);
  
  // Simulate seller response
  simulateSellerResponse(offer);
  
  saveState();
}

// Simulate seller response to offer
function simulateSellerResponse(offer) {
  setTimeout(() => {
    const random = Math.random();
    
    if (random < 0.3) {
      // Accept offer
      acceptOffer(offer.id);
    } else if (random < 0.7) {
      // Counter offer
      const counterPrice = Math.round(offer.price * (1.05 + Math.random() * 0.1)); // 5-15% higher
      createCounterOffer(offer.id, {
        price: counterPrice,
        earnestMoney: Math.round(counterPrice * 0.05),
        buyerComments: 'Counter-offer: Price adjustment requested',
        modifiedBy: state.currentListing.owner
      });
    } else {
      // Reject offer
      rejectOffer(offer.id, 'Price below expectations');
    }
  }, 2000 + Math.random() * 3000); // 2-5 seconds delay
}

// Accept offer
function acceptOffer(offerId) {
  const offer = state.offers.find(o => o.id === offerId);
  if (!offer) return;
  
  offer.status = 'accepted';
  offer.acceptedAt = Date.now();
  offer.history.push({
    action: 'accepted',
    timestamp: Date.now(),
    actor: state.currentListing.owner,
    details: `Offer accepted for ${fmt(offer.price)}`
  });
  
  notify(`Your offer of ${fmt(offer.price)} has been accepted! Proceeding to contract generation.`);
  
  // Generate PSA contract
  generatePSAContract(offer);
  
  // Open escrow
  setTimeout(() => {
    openEnhancedEscrow(offer);
  }, 1000);
  
  saveState();
}

// Create counter offer
function createCounterOffer(originalOfferId, counterData) {
  const originalOffer = state.offers.find(o => o.id === originalOfferId);
  if (!originalOffer) return;
  
  // Update original offer status
  originalOffer.status = 'countered';
  originalOffer.history.push({
    action: 'countered',
    timestamp: Date.now(),
    actor: counterData.modifiedBy,
    details: `Counter-offer created with price ${fmt(counterData.price)}`
  });
  
  // Create counter offer
  const counterOffer = {
    ...originalOffer,
    id: state.offerIdCounter++,
    version: originalOffer.version + 1,
    parentOfferId: originalOfferId,
    status: 'counter_submitted',
    price: counterData.price,
    earnestMoney: counterData.earnestMoney,
    buyerComments: counterData.buyerComments,
    modifiedBy: counterData.modifiedBy,
    modifiedAt: Date.now(),
    history: [
      ...originalOffer.history,
      {
        action: 'counter_created',
        timestamp: Date.now(),
        actor: counterData.modifiedBy,
        details: `Counter-offer v${originalOffer.version + 1} created`
      }
    ]
  };
  
  // Add to arrays
  state.currentListing.offers.push(counterOffer);
  state.offers.push(counterOffer);
  state.currentOffer = counterOffer;
  
  // Show counter offer modal
  showCounterOfferModal(counterOffer);
  
  notify(`Seller sent a counter-offer: ${fmt(counterData.price)}`);
  saveState();
}

// Show counter offer modal
function showCounterOfferModal(counterOffer) {
  const originalOffer = state.offers.find(o => o.id === counterOffer.parentOfferId);
  
  $('#counterOfferOriginal').textContent = fmt(originalOffer.price);
  $('#counterOfferNew').textContent = fmt(counterOffer.price);
  $('#counterOfferDetails').innerHTML = `
    <div><strong>Changes:</strong></div>
    <div>Price: ${fmt(originalOffer.price)} → ${fmt(counterOffer.price)}</div>
    <div>Earnest Money: ${fmt(originalOffer.earnestMoney)} → ${fmt(counterOffer.earnestMoney)}</div>
    ${counterOffer.buyerComments ? `<div><strong>Comments:</strong> ${counterOffer.buyerComments}</div>` : ''}
  `;
  
  ModalManager.openModal('counterOfferModal');
}

// Accept counter offer
function acceptCounterOffer() {
  if (!state.currentOffer) return;
  
  state.currentOffer.status = 'accepted';
  state.currentOffer.acceptedAt = Date.now();
  state.currentOffer.history.push({
    action: 'counter_accepted',
    timestamp: Date.now(),
    actor: state.user.email,
    details: `Counter-offer accepted for ${fmt(state.currentOffer.price)}`
  });
  
  ModalManager.closeModal('counterOfferModal');
  notify(`Counter-offer accepted! Proceeding to contract generation.`);
  
  // Generate PSA contract
  generatePSAContract(state.currentOffer);
  
  // Open escrow
  setTimeout(() => {
    openEnhancedEscrow(state.currentOffer);
  }, 1000);
  
  saveState();
}

// Reject counter offer
function rejectCounterOffer() {
  if (!state.currentOffer) return;
  
  state.currentOffer.status = 'rejected';
  state.currentOffer.rejectedAt = Date.now();
  state.currentOffer.history.push({
    action: 'counter_rejected',
    timestamp: Date.now(),
    actor: state.user.email,
    details: 'Counter-offer rejected by buyer'
  });
  
  ModalManager.closeModal('counterOfferModal');
  notify('Counter-offer rejected.');
  saveState();
}

// Reject offer
function rejectOffer(offerId, reason = '') {
  const offer = state.offers.find(o => o.id === offerId);
  if (!offer) return;
  
  offer.status = 'rejected';
  offer.rejectedAt = Date.now();
  offer.rejectionReason = reason;
  offer.history.push({
    action: 'rejected',
    timestamp: Date.now(),
    actor: state.currentListing.owner,
    details: `Offer rejected: ${reason}`
  });
  
  notify(`Your offer has been rejected. ${reason ? 'Reason: ' + reason : ''}`);
  saveState();
}

// Legacy offer function for backward compatibility
function submitOffer() {
  // Redirect to enhanced offer modal
  ModalManager.closeModal('offerModal');
  openEnhancedOfferModal();
}

/* ===== PSA CONTRACT GENERATION ===== */

// Generate PSA contract from accepted offer
function generatePSAContract(offer) {
  const listing = state.listings.find(l => l.id === offer.listingId);
  if (!listing) return;
  
  const contract = {
    id: state.contractIdCounter++,
    offerId: offer.id,
    listingId: listing.id,
    buyer: offer.buyer,
    seller: listing.owner,
    purchasePrice: offer.price,
    earnestMoney: offer.earnestMoney,
    closingDate: offer.closingDate,
    contingencies: offer.contingencies,
    inclusions: offer.inclusions || '',
    exclusions: offer.exclusions || '',
    specialTerms: offer.specialTerms || '',
    status: 'generated',
    generatedAt: Date.now(),
    signaturesRequired: 2,
    signaturesCollected: 0,
    signatures: [],
    property: {
      title: listing.title,
      description: listing.desc,
      area: listing.area,
      zoning: listing.zoning,
      location: `${listing.barangay}, ${listing.city}, ${listing.province}`,
      coordinates: listing.coords
    }
  };
  
  state.contracts.push(contract);
  
  // Add to offer history
  offer.history.push({
    action: 'psa_generated',
    timestamp: Date.now(),
    actor: 'system',
    details: `PSA contract #${contract.id} generated`
  });
  
  notify(`PSA contract #${contract.id} has been generated and is ready for signatures.`);
  
  // Auto-sign for seller (simulated)
  setTimeout(() => {
    signPSAContract(contract.id, listing.owner, 'seller');
  }, 2000);
  
  saveState();
  return contract;
}

// Sign PSA contract
function signPSAContract(contractId, signerEmail, signerRole) {
  const contract = state.contracts.find(c => c.id === contractId);
  if (!contract) return;
  
  // Check if already signed by this person
  if (contract.signatures.find(s => s.signer === signerEmail)) {
    notify('You have already signed this contract.');
    return;
  }
  
  const signature = {
    signer: signerEmail,
    role: signerRole,
    signedAt: Date.now(),
    ipAddress: '127.0.0.1', // Simulated
    signatureHash: 'SHA256:' + Math.random().toString(36).substr(2, 16) // Simulated
  };
  
  contract.signatures.push(signature);
  contract.signaturesCollected++;
  
  // Check if fully executed
  if (contract.signaturesCollected >= contract.signaturesRequired) {
    contract.status = 'fully_executed';
    contract.executedAt = Date.now();
    notify(`PSA contract #${contractId} is now fully executed! Proceeding to title transfer.`);
    
    // Start title transfer process
    setTimeout(() => {
      initiateTitleTransfer(contractId);
    }, 1000);
  } else {
    contract.status = 'partially_signed';
    notify(`PSA contract #${contractId} signed by ${signerRole}. Waiting for ${contract.signaturesRequired - contract.signaturesCollected} more signature(s).`);
  }
  
  saveState();
}

// Initiate title transfer process
function initiateTitleTransfer(contractId) {
  const contract = state.contracts.find(c => c.id === contractId);
  if (!contract) return;
  
  const titleVerification = {
    id: Date.now(),
    contractId: contractId,
    listingId: contract.listingId,
    status: 'initiated',
    initiatedAt: Date.now(),
    steps: [
      { name: 'Registry of Deeds Verification', status: 'pending', completedAt: null },
      { name: 'Tax Clearance', status: 'pending', completedAt: null },
      { name: 'Zoning Compliance Check', status: 'pending', completedAt: null },
      { name: 'Title Transfer Documentation', status: 'pending', completedAt: null },
      { name: 'New Title Issuance', status: 'pending', completedAt: null }
    ]
  };
  
  state.titleVerifications.push(titleVerification);
  
  notify('Title transfer process initiated. This may take 15-30 business days.');
  
  // Simulate completion of title transfer steps
  simulateTitleTransferSteps(titleVerification.id);
  
  saveState();
}

// Simulate title transfer steps completion
function simulateTitleTransferSteps(verificationId) {
  const verification = state.titleVerifications.find(v => v.id === verificationId);
  if (!verification) return;
  
  let currentStepIndex = 0;
  
  const completeNextStep = () => {
    if (currentStepIndex >= verification.steps.length) {
      verification.status = 'completed';
      verification.completedAt = Date.now();
      notify('🎉 Title transfer completed! New title issued to buyer.');
      return;
    }
    
    const step = verification.steps[currentStepIndex];
    step.status = 'completed';
    step.completedAt = Date.now();
    
    notify(`✅ ${step.name} completed (${currentStepIndex + 1}/${verification.steps.length})`);
    
    currentStepIndex++;
    
    // Schedule next step (3-7 seconds delay to simulate processing time)
    setTimeout(completeNextStep, 3000 + Math.random() * 4000);
  };
  
  // Start with first step after a short delay
  setTimeout(completeNextStep, 2000);
}

/* ===== ENHANCED ESCROW SYSTEM ===== */

// Open enhanced escrow modal
function openEnhancedEscrow(offer) {
  const listing = state.listings.find(l => l.id === offer.listingId);
  if (!listing) return;
  
  // Create or find escrow account
  let escrowAccount = state.escrowAccounts.find(e => e.offerId === offer.id);
  
  if (!escrowAccount) {
    escrowAccount = {
      id: state.escrowIdCounter++,
      offerId: offer.id,
      listingId: listing.id,
      buyer: offer.buyer,
      seller: listing.owner,
      totalAmount: offer.price,
      earnestMoneyAmount: offer.earnestMoney,
      status: 'pending_deposit',
      createdAt: Date.now(),
      transactions: [],
      milestones: [
        { name: 'Earnest Money Deposit', amount: offer.earnestMoney, status: 'pending', dueDate: Date.now() + (24 * 60 * 60 * 1000) },
        { name: 'Down Payment', amount: Math.round(offer.price * 0.2), status: 'pending', dueDate: new Date(offer.closingDate).getTime() - (7 * 24 * 60 * 60 * 1000) },
        { name: 'Final Payment', amount: offer.price - offer.earnestMoney - Math.round(offer.price * 0.2), status: 'pending', dueDate: new Date(offer.closingDate).getTime() }
      ]
    };
    
    state.escrowAccounts.push(escrowAccount);
  }
  
  state.currentEscrowAccount = escrowAccount;
  
  // Populate enhanced escrow modal
  populateEnhancedEscrowModal(escrowAccount, listing);
  
  ModalManager.closeModal('escrowModal'); // Close simple escrow if open
  ModalManager.openModal('enhancedEscrowModal');
  
  saveState();
}

// Populate enhanced escrow modal with account details
function populateEnhancedEscrowModal(escrowAccount, listing) {
  // Create enhanced escrow modal if it doesn't exist
  if (!$('#enhancedEscrowModal')) {
    createEnhancedEscrowModal();
  }
  
  $('#escrowAccountId').textContent = `ESC-${escrowAccount.id.toString().padStart(6, '0')}`;
  $('#escrowTotalAmount').textContent = fmt(escrowAccount.totalAmount);
  $('#escrowProperty').textContent = listing.title;
  $('#escrowBuyer').textContent = escrowAccount.buyer;
  $('#escrowSeller').textContent = escrowAccount.seller;
  $('#escrowStatus').textContent = escrowAccount.status.replace('_', ' ').toUpperCase();
  
  // Render milestones
  const milestonesContainer = $('#escrowMilestones');
  milestonesContainer.innerHTML = escrowAccount.milestones.map(milestone => `
    <div class="milestone-item ${milestone.status}">
      <div class="milestone-info">
        <div class="milestone-name">${milestone.name}</div>
        <div class="milestone-amount">${fmt(milestone.amount)}</div>
        <div class="milestone-due">Due: ${new Date(milestone.dueDate).toLocaleDateString()}</div>
      </div>
      <div class="milestone-status">${milestone.status.toUpperCase()}</div>
      ${milestone.status === 'pending' && milestone.name === 'Earnest Money Deposit' ? 
        `<button class="btn primary btn-sm" onclick="depositEarnestMoney()">Deposit Now</button>` : ''}
    </div>
  `).join('');
  
  // Render transaction history
  const transactionsContainer = $('#escrowTransactions');
  if (escrowAccount.transactions.length > 0) {
    transactionsContainer.innerHTML = escrowAccount.transactions.map(tx => `
      <div class="transaction-item">
        <div class="tx-type">${tx.type.toUpperCase()}</div>
        <div class="tx-amount">${tx.type === 'deposit' ? '+' : '-'}${fmt(tx.amount)}</div>
        <div class="tx-date">${new Date(tx.timestamp).toLocaleDateString()}</div>
        <div class="tx-description">${tx.description}</div>
      </div>
    `).join('');
  } else {
    transactionsContainer.innerHTML = '<div class="no-transactions">No transactions yet</div>';
  }
}

// Create enhanced escrow modal dynamically
function createEnhancedEscrowModal() {
  const modal = document.createElement('div');
  modal.id = 'enhancedEscrowModal';
  modal.className = 'modal hidden';
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 700px;">
      <button class="modal-close">✕</button>
      <h3>🏦 Escrow Account Details</h3>
      
      <div class="escrow-summary">
        <div class="escrow-info-grid">
          <div><strong>Account ID:</strong> <span id="escrowAccountId">ESC-000001</span></div>
          <div><strong>Total Amount:</strong> <span id="escrowTotalAmount">₱0</span></div>
          <div><strong>Property:</strong> <span id="escrowProperty">Property Name</span></div>
          <div><strong>Buyer:</strong> <span id="escrowBuyer">buyer@email.com</span></div>
          <div><strong>Seller:</strong> <span id="escrowSeller">seller@email.com</span></div>
          <div><strong>Status:</strong> <span id="escrowStatus">PENDING</span></div>
        </div>
      </div>
      
      <div class="escrow-section">
        <h4>💰 Payment Milestones</h4>
        <div id="escrowMilestones" class="milestones-list">
          <!-- Milestones will be populated here -->
        </div>
      </div>
      
      <div class="escrow-section">
        <h4>📋 Transaction History</h4>
        <div id="escrowTransactions" class="transactions-list">
          <!-- Transactions will be populated here -->
        </div>
      </div>
      
      <div class="escrow-actions">
        <button class="btn ghost modal-close">Close</button>
        <button class="btn" onclick="downloadEscrowStatement()">Download Statement</button>
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
  ModalManager.setupModalCloseButtons('enhancedEscrowModal');
}

// Deposit earnest money
function depositEarnestMoney() {
  if (!state.currentEscrowAccount) return;
  
  const earnestMilestone = state.currentEscrowAccount.milestones.find(m => m.name === 'Earnest Money Deposit');
  if (!earnestMilestone || earnestMilestone.status !== 'pending') return;
  
  // Simulate payment processing
  ModalManager.showLoading('Processing earnest money deposit...');
  
  setTimeout(() => {
    // Update milestone status
    earnestMilestone.status = 'completed';
    earnestMilestone.completedAt = Date.now();
    
    // Add transaction record
    const transaction = {
      id: Date.now(),
      type: 'deposit',
      amount: earnestMilestone.amount,
      description: 'Earnest Money Deposit',
      timestamp: Date.now(),
      from: state.currentEscrowAccount.buyer,
      reference: `EMD-${Date.now().toString().substr(-6)}`
    };
    
    state.currentEscrowAccount.transactions.push(transaction);
    state.currentEscrowAccount.status = 'earnest_deposited';
    
    ModalManager.hideLoading();
    
    // Refresh modal
    const listing = state.listings.find(l => l.id === state.currentEscrowAccount.listingId);
    populateEnhancedEscrowModal(state.currentEscrowAccount, listing);
    
    notify(`✅ Earnest money of ${fmt(earnestMilestone.amount)} deposited successfully!`);
    
    saveState();
  }, 2000 + Math.random() * 2000);
}

// Download escrow statement
function downloadEscrowStatement() {
  if (!state.currentEscrowAccount) return;
  
  const account = state.currentEscrowAccount;
  const listing = state.listings.find(l => l.id === account.listingId);
  
  let statement = `TERRATRADE ESCROW STATEMENT\n`;
  statement += `Account ID: ESC-${account.id.toString().padStart(6, '0')}\n`;
  statement += `Generated: ${new Date().toLocaleString()}\n\n`;
  statement += `PROPERTY: ${listing.title}\n`;
  statement += `BUYER: ${account.buyer}\n`;
  statement += `SELLER: ${account.seller}\n`;
  statement += `TOTAL AMOUNT: ${fmt(account.totalAmount)}\n\n`;
  statement += `PAYMENT MILESTONES:\n`;
  statement += `${'='.repeat(50)}\n`;
  
  account.milestones.forEach(milestone => {
    statement += `${milestone.name}: ${fmt(milestone.amount)} - ${milestone.status.toUpperCase()}\n`;
    if (milestone.completedAt) {
      statement += `  Completed: ${new Date(milestone.completedAt).toLocaleString()}\n`;
    }
    statement += `\n`;
  });
  
  if (account.transactions.length > 0) {
    statement += `TRANSACTION HISTORY:\n`;
    statement += `${'='.repeat(50)}\n`;
    account.transactions.forEach(tx => {
      statement += `${new Date(tx.timestamp).toLocaleString()} | ${tx.type.toUpperCase()} | ${fmt(tx.amount)} | ${tx.description}\n`;
    });
  }
  
  // Create download
  const blob = new Blob([statement], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `escrow_statement_ESC-${account.id.toString().padStart(6, '0')}_${Date.now()}.txt`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  
  notify('Escrow statement downloaded');
}

/* escrow */
function openEscrow(amount){
  $("#escrowStatus").textContent = `Awaiting deposit of ${fmt(amount)} into escrow.`;
  $("#depositFunds").classList.remove("hidden");
  $("#releaseFunds").classList.add("hidden");
  $("#releaseFunds").disabled = false;
  $("#escrowModal").classList.remove("hidden");
}

/* auction */
function openAuction(listingId){
  const l = state.listings.find(x=>x.id===listingId);
  if (!l) return;
  state.currentAuction = { id: l.id, title: l.title, topBid: l.price, topBidder: null, endsAt: l.auctionEnds || (Date.now()+1000*60*2) };
  $("#auctionInfo").innerHTML = `<div>Top bid: ${fmt(state.currentAuction.topBid)}</div><div>Ends in: <span id="auctionTimer"></span></div>`;
  $("#bidAmount").value = Math.max(state.currentAuction.topBid + 1000, state.currentAuction.topBid);
  $("#auctionModal").classList.remove("hidden");
  startAuctionTimer();
}
let auctionTimerInterval = null;
function startAuctionTimer(){
  if (auctionTimerInterval) clearInterval(auctionTimerInterval);
  auctionTimerInterval = setInterval(()=>{
    if (!state.currentAuction) return clearInterval(auctionTimerInterval);
    const remaining = state.currentAuction.endsAt - Date.now();
    const el = $("#auctionTimer");
    if (!el) return;
    if (remaining <= 0){
      el.textContent = "Ended";
      clearInterval(auctionTimerInterval);
      notify(`Auction ended for ${state.currentAuction.title}. Winner: ${state.currentAuction.topBidder || "none"} at ${fmt(state.currentAuction.topBid)}`);
      alert(`Auction ended. Winner: ${state.currentAuction.topBidder || "none"} at ${fmt(state.currentAuction.topBid)}`);
      $("#auctionModal").classList.add("hidden");
      state.currentAuction = null;
      return;
    }
    const mins = Math.floor(remaining / 60000);
    const secs = Math.floor((remaining % 60000) / 1000);
    el.textContent = `${mins}m ${secs}s`;
  }, 500);
}


/* ===== MY LISTINGS MANAGEMENT ===== */

let currentEditingListing = null;
let filteredMyListings = [];

/* My listings for seller/broker */
function openMyListings(){
  if (!state.user) return alert("Login first.");
  
  // All users can access this functionality
  // Removed role restrictions - all users can manage listings
  
  const myListings = state.listings.filter(l => l.owner === state.user.email);
  filteredMyListings = [...myListings];
  
  populateMyListingsModal(myListings);
  ModalManager.openModal('myListingsModal');
  
  // Setup event handlers for the modal
  setupMyListingsHandlers();
}

function populateMyListingsModal(listings) {
  // Update summary stats
  updateListingsSummaryStats(listings);
  
  // Render listings grid
  renderMyListingsGrid(listings);
}

function updateListingsSummaryStats(listings) {
  const totalCount = listings.length;
  const activeCount = listings.filter(l => getListingStatus(l) === 'active').length;
  const pendingCount = listings.filter(l => getListingStatus(l) === 'pending').length;
  const totalValue = listings.reduce((sum, l) => sum + l.price, 0);
  
  $('#totalListingsCount').textContent = totalCount;
  $('#activeListingsCount').textContent = activeCount;
  $('#pendingListingsCount').textContent = pendingCount;
  $('#totalListingsValue').textContent = fmt(totalValue);
}

function getListingStatus(listing) {
  // Simulate different listing statuses
  if (listing.id % 4 === 0) return 'sold';
  if (listing.id % 3 === 0) return 'pending';
  if (listing.type === 'auction' && listing.auctionEnds < Date.now()) return 'expired';
  return 'active';
}

function renderMyListingsGrid(listings) {
  const container = $('#myListingsGrid');
  const emptyState = $('#myListingsEmpty');
  
  if (listings.length === 0) {
    container.classList.add('hidden');
    emptyState.classList.remove('hidden');
    return;
  }
  
  container.classList.remove('hidden');
  emptyState.classList.add('hidden');
  
  container.innerHTML = listings.map(listing => {
    const status = getListingStatus(listing);
    const statusIcon = getStatusIcon(status);
    const views = Math.floor(Math.random() * 100) + 10; // Simulated views
    const inquiries = Math.floor(Math.random() * 20) + 1; // Simulated inquiries
    const daysListed = Math.floor((Date.now() - (listing.submittedAt ? new Date(listing.submittedAt).getTime() : Date.now() - 86400000 * 30)) / 86400000);
    
    return `
      <div class="listing-card" data-id="${listing.id}">
        <div class="listing-card-header">
          <div class="listing-card-title">${listing.title}</div>
          <div class="listing-card-meta">
            <div class="listing-status ${status}">
              <div class="listing-status-icon"></div>
              ${status.toUpperCase()}
            </div>
            <div>${listing.zoning}</div>
            <div>${listing.type === 'auction' ? 'Auction' : 'For Sale'}</div>
          </div>
        </div>
        
        <div class="listing-card-body">
          <div class="listing-stats">
            <div class="listing-stat">
              <div class="listing-stat-value">${fmt(listing.price)}</div>
              <div class="listing-stat-label">Price</div>
            </div>
            <div class="listing-stat">
              <div class="listing-stat-value">${listing.area || 'N/A'}</div>
              <div class="listing-stat-label">Sqm</div>
            </div>
            <div class="listing-stat">
              <div class="listing-stat-value">${views}</div>
              <div class="listing-stat-label">Views</div>
            </div>
            <div class="listing-stat">
              <div class="listing-stat-value">${inquiries}</div>
              <div class="listing-stat-label">Inquiries</div>
            </div>
          </div>
          
          <div class="listing-description">
            ${listing.desc || 'No description available.'}
          </div>
        </div>
        
        <div class="listing-card-footer">
          <div class="listing-actions">
            <div class="listing-actions-left">
              <button class="btn btn-sm primary" onclick="editListing(${listing.id})">
                ✏️ Edit
              </button>
              <button class="btn btn-sm" onclick="viewListingAnalytics(${listing.id})">
                📊 Analytics
              </button>
              <button class="btn btn-sm ghost" onclick="deleteListing(${listing.id})">
                🗑️ Delete
              </button>
            </div>
            <div class="listing-actions-right">
              <button class="btn-icon" onclick="duplicateListing(${listing.id})" title="Duplicate">
                📋
              </button>
              <button class="btn-icon" onclick="shareListing(${listing.id})" title="Share">
                🔗
              </button>
              <div class="listing-views">
                👁️ ${views}
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function getStatusIcon(status) {
  const icons = {
    active: '🟢',
    pending: '🟡', 
    sold: '🔵',
    expired: '🔴'
  };
  return icons[status] || '⚪';
}

function setupMyListingsHandlers() {
  // Search functionality
  $('#listingsSearchInput').addEventListener('input', (e) => {
    filterMyListings(e.target.value);
  });
  
  // Status filter
  $('#listingsStatusFilter').addEventListener('change', (e) => {
    filterMyListingsByStatus(e.target.value);
  });
  
  // Type filter
  $('#listingsTypeFilter').addEventListener('change', (e) => {
    filterMyListingsByType(e.target.value);
  });
  
  // Add new listing button
  $('#addNewListingBtn').addEventListener('click', () => {
    ModalManager.closeModal('myListingsModal');
    openLandSellingModal();
  });
  
  // Export listings button
  $('#exportListingsBtn').addEventListener('click', exportMyListings);
  
  // Bulk actions button
  $('#bulkActionsBtn').addEventListener('click', showBulkActionsMenu);
  
  // Edit listing form handlers
  setupEditListingHandlers();
}

function filterMyListings(searchTerm) {
  const myListings = state.listings.filter(l => l.owner === state.user.email);
  
  const filtered = myListings.filter(listing => 
    listing.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    listing.desc.toLowerCase().includes(searchTerm.toLowerCase()) ||
    listing.city.toLowerCase().includes(searchTerm.toLowerCase()) ||
    listing.province.toLowerCase().includes(searchTerm.toLowerCase())
  );
  
  filteredMyListings = filtered;
  renderMyListingsGrid(filtered);
  updateListingsSummaryStats(filtered);
}

function filterMyListingsByStatus(status) {
  const myListings = state.listings.filter(l => l.owner === state.user.email);
  
  const filtered = status ? myListings.filter(l => getListingStatus(l) === status) : myListings;
  
  filteredMyListings = filtered;
  renderMyListingsGrid(filtered);
  updateListingsSummaryStats(filtered);
}

function filterMyListingsByType(type) {
  const myListings = state.listings.filter(l => l.owner === state.user.email);
  
  const filtered = type ? myListings.filter(l => l.type === type) : myListings;
  
  filteredMyListings = filtered;
  renderMyListingsGrid(filtered);
  updateListingsSummaryStats(filtered);
}

// Edit listing functionality
function editListing(listingId) {
  const listing = state.listings.find(l => l.id === listingId);
  if (!listing) return;
  
  currentEditingListing = listing;
  
  // Populate edit form
  $('#editListingTitle').value = listing.title;
  $('#editListingPrice').value = listing.price;
  $('#editListingZoning').value = listing.zoning;
  $('#editListingArea').value = listing.area || '';
  $('#editListingLocation').value = `${listing.barangay}, ${listing.city}, ${listing.province}`;
  $('#editListingDescription').value = listing.desc || '';
  $('#editListingType').value = listing.type;
  
  // Handle auction end date
  if (listing.type === 'auction' && listing.auctionEnds) {
    const auctionEnd = new Date(listing.auctionEnds);
    $('#editAuctionEnd').value = auctionEnd.toISOString().slice(0, 16);
    $('#editAuctionEndGroup').style.display = 'block';
  } else {
    $('#editAuctionEndGroup').style.display = 'none';
  }
  
  ModalManager.openModal('editListingModal', { stack: true });
}

function setupEditListingHandlers() {
  // Type change handler
  $('#editListingType').addEventListener('change', (e) => {
    const isAuction = e.target.value === 'auction';
    $('#editAuctionEndGroup').style.display = isAuction ? 'block' : 'none';
  });
  
  // Save changes button
  $('#saveListingChangesBtn').addEventListener('click', saveListingChanges);
}

function saveListingChanges() {
  if (!currentEditingListing) return;
  
  // Validate form
  const title = $('#editListingTitle').value.trim();
  const price = Number($('#editListingPrice').value);
  const zoning = $('#editListingZoning').value;
  const area = Number($('#editListingArea').value);
  const location = $('#editListingLocation').value.trim();
  
  if (!title || !price || !zoning || !location) {
    return ModalManager.showAlert({
      title: 'Validation Error',
      message: 'Please fill in all required fields.'
    });
  }
  
  // Parse location
  const locationParts = location.split(',').map(s => s.trim());
  const barangay = locationParts[0] || '';
  const city = locationParts[1] || '';
  const province = locationParts[2] || '';
  
  // Update listing
  currentEditingListing.title = title;
  currentEditingListing.price = price;
  currentEditingListing.zoning = zoning;
  currentEditingListing.area = area;
  currentEditingListing.barangay = barangay;
  currentEditingListing.city = city;
  currentEditingListing.province = province;
  currentEditingListing.desc = $('#editListingDescription').value.trim();
  currentEditingListing.type = $('#editListingType').value;
  
  // Handle auction end date
  if (currentEditingListing.type === 'auction') {
    const auctionEndValue = $('#editAuctionEnd').value;
    if (auctionEndValue) {
      currentEditingListing.auctionEnds = new Date(auctionEndValue).getTime();
    }
  }
  
  // Close modal and refresh
  ModalManager.closeModal('editListingModal');
  
  // Refresh the my listings view
  const myListings = state.listings.filter(l => l.owner === state.user.email);
  populateMyListingsModal(myListings);
  
  notify(`Listing "${title}" updated successfully.`);
  currentEditingListing = null;
}

// Delete listing functionality
async function deleteListing(listingId) {
  const listing = state.listings.find(l => l.id === listingId);
  if (!listing) return;
  
  ModalManager.showConfirm({
    title: 'Delete Listing',
    message: `Are you sure you want to delete "${listing.title}"? This action cannot be undone.`,
    confirmText: 'Delete',
    cancelText: 'Cancel',
    onConfirm: async () => {
      try {
        // Show loading
        ModalManager.showLoading('Deleting property...');
        
        // Make API call to delete the property
        const response = await fetch(`api/my-listings-api.php?action=delete&id=${listingId}`, {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
          },
          credentials: 'include'
        });
        
        const result = await response.json();
        
        // Hide loading
        ModalManager.hideLoading();
        
        if (result.success) {
          // Remove from local state
          state.listings = state.listings.filter(l => l.id !== listingId);
          
          // Refresh views
          renderListings();
          const myListings = state.listings.filter(l => l.owner === state.user.email);
          populateMyListingsModal(myListings);
          
          // Save updated state
          saveState();
          
          notify(`Listing "${listing.title}" deleted successfully.`);
        } else {
          // Handle API errors
          ModalManager.showAlert({
            title: 'Delete Failed',
            message: result.error || 'Failed to delete the property. Please try again.',
            buttonText: 'OK'
          });
        }
        
      } catch (error) {
        console.error('Delete error:', error);
        ModalManager.hideLoading();
        
        ModalManager.showAlert({
          title: 'Error',
          message: 'An error occurred while deleting the property. Please check your connection and try again.',
          buttonText: 'OK'
        });
      }
    }
  });
}

// Duplicate listing functionality
function duplicateListing(listingId) {
  const listing = state.listings.find(l => l.id === listingId);
  if (!listing) return;
  
  const newListing = {
    ...listing,
    id: Date.now() + Math.random(), // New unique ID
    title: `${listing.title} (Copy)`,
    submittedAt: new Date().toLocaleString()
  };
  
  state.listings.unshift(newListing);
  
  // Refresh view
  const myListings = state.listings.filter(l => l.owner === state.user.email);
  populateMyListingsModal(myListings);
  
  notify(`Listing "${listing.title}" duplicated successfully.`);
}

// Share listing functionality
function shareListing(listingId) {
  const listing = state.listings.find(l => l.id === listingId);
  if (!listing) return;
  
  // Simulate sharing URL
  const shareUrl = `https://terratrade.ph/listing/${listingId}`;
  
  if (navigator.clipboard) {
    navigator.clipboard.writeText(shareUrl).then(() => {
      notify('Listing URL copied to clipboard!');
    }).catch(() => {
      fallbackCopyToClipboard(shareUrl);
    });
  } else {
    fallbackCopyToClipboard(shareUrl);
  }
}

function fallbackCopyToClipboard(text) {
  const textArea = document.createElement('textarea');
  textArea.value = text;
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();
  
  try {
    document.execCommand('copy');
    notify('Listing URL copied to clipboard!');
  } catch (err) {
    ModalManager.showAlert({
      title: 'Share Listing',
      message: `Share this URL: ${text}`
    });
  }
  
  document.body.removeChild(textArea);
}

// View listing analytics
function viewListingAnalytics(listingId) {
  const listing = state.listings.find(l => l.id === listingId);
  if (!listing) return;
  
  // Generate simulated analytics data
  const analytics = generateListingAnalytics(listing);
  
  populateAnalyticsModal(listing, analytics);
  ModalManager.openModal('listingAnalyticsModal', { stack: true });
}

function generateListingAnalytics(listing) {
  const daysListed = Math.floor((Date.now() - (listing.submittedAt ? new Date(listing.submittedAt).getTime() : Date.now() - 86400000 * 30)) / 86400000);
  
  return {
    views: Math.floor(Math.random() * 500) + 50,
    uniqueViews: Math.floor(Math.random() * 300) + 30,
    inquiries: Math.floor(Math.random() * 50) + 5,
    favorites: Math.floor(Math.random() * 25) + 2,
    shares: Math.floor(Math.random() * 10) + 1,
    daysListed: daysListed,
    avgViewsPerDay: Math.floor((Math.random() * 500 + 50) / Math.max(daysListed, 1)),
    conversionRate: ((Math.random() * 5) + 1).toFixed(1) + '%',
    priceViews: Math.floor(Math.random() * 200) + 20,
    contactViews: Math.floor(Math.random() * 100) + 10
  };
}

function populateAnalyticsModal(listing, analytics) {
  const content = $('#analyticsContent');
  
  content.innerHTML = `
    <div class="analytics-section">
      <div class="analytics-title">📊 Performance Overview</div>
      <div class="analytics-grid">
        <div class="analytics-card">
          <div class="analytics-value">${analytics.views}</div>
          <div class="analytics-label">Total Views</div>
        </div>
        <div class="analytics-card">
          <div class="analytics-value">${analytics.uniqueViews}</div>
          <div class="analytics-label">Unique Views</div>
        </div>
        <div class="analytics-card">
          <div class="analytics-value">${analytics.inquiries}</div>
          <div class="analytics-label">Inquiries</div>
        </div>
        <div class="analytics-card">
          <div class="analytics-value">${analytics.conversionRate}</div>
          <div class="analytics-label">Conversion Rate</div>
        </div>
      </div>
    </div>
    
    <div class="analytics-section">
      <div class="analytics-title">👥 User Engagement</div>
      <div class="analytics-grid">
        <div class="analytics-card">
          <div class="analytics-value">${analytics.favorites}</div>
          <div class="analytics-label">Favorites</div>
        </div>
        <div class="analytics-card">
          <div class="analytics-value">${analytics.shares}</div>
          <div class="analytics-label">Shares</div>
        </div>
        <div class="analytics-card">
          <div class="analytics-value">${analytics.contactViews}</div>
          <div class="analytics-label">Contact Views</div>
        </div>
        <div class="analytics-card">
          <div class="analytics-value">${analytics.avgViewsPerDay}</div>
          <div class="analytics-label">Avg Views/Day</div>
        </div>
      </div>
    </div>
    
    <div class="analytics-section">
      <div class="analytics-title">📈 Listing Details</div>
      <div style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
        <div style="margin-bottom: 10px;"><strong>Listed:</strong> ${analytics.daysListed} days ago</div>
        <div style="margin-bottom: 10px;"><strong>Status:</strong> ${getListingStatus(listing).toUpperCase()}</div>
        <div style="margin-bottom: 10px;"><strong>Price Views:</strong> ${analytics.priceViews}</div>
        <div><strong>Type:</strong> ${listing.type === 'auction' ? 'Auction' : 'For Sale'}</div>
      </div>
    </div>
  `;
}

// Export listings functionality
function exportMyListings() {
  const myListings = state.listings.filter(l => l.owner === state.user.email);
  
  let csvContent = "data:text/csv;charset=utf-8,";
  csvContent += "Title,Price,Zoning,Area,Location,Status,Type,Description\n";
  
  myListings.forEach(listing => {
    const location = `${listing.barangay}, ${listing.city}, ${listing.province}`;
    const status = getListingStatus(listing);
    const row = [
      `"${listing.title}"`,
      listing.price,
      listing.zoning,
      listing.area || '',
      `"${location}"`,
      status,
      listing.type,
      `"${(listing.desc || '').replace(/"/g, '""')}"`
    ].join(',');
    csvContent += row + "\n";
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `my_listings_${Date.now()}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  notify('Listings exported successfully!');
}

// Bulk actions functionality
function showBulkActionsMenu() {
  ModalManager.showAlert({
    title: 'Bulk Actions',
    message: 'Bulk actions feature coming soon! You can currently edit listings individually.'
  });
}

// Download analytics report
function downloadAnalyticsReport() {
  if (!currentEditingListing) return;
  
  const analytics = generateListingAnalytics(currentEditingListing);
  
  let report = `TERRATRADE LISTING ANALYTICS REPORT\n`;
  report += `Listing: ${currentEditingListing.title}\n`;
  report += `Generated: ${new Date().toLocaleString()}\n\n`;
  report += `PERFORMANCE METRICS:\n`;
  report += `Total Views: ${analytics.views}\n`;
  report += `Unique Views: ${analytics.uniqueViews}\n`;
  report += `Inquiries: ${analytics.inquiries}\n`;
  report += `Conversion Rate: ${analytics.conversionRate}\n\n`;
  report += `ENGAGEMENT:\n`;
  report += `Favorites: ${analytics.favorites}\n`;
  report += `Shares: ${analytics.shares}\n`;
  report += `Contact Views: ${analytics.contactViews}\n\n`;
  report += `DETAILS:\n`;
  report += `Days Listed: ${analytics.daysListed}\n`;
  report += `Average Views per Day: ${analytics.avgViewsPerDay}\n`;
  report += `Status: ${getListingStatus(currentEditingListing).toUpperCase()}\n`;
  
  const blob = new Blob([report], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `listing_analytics_${currentEditingListing.id}_${Date.now()}.txt`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  
  notify('Analytics report downloaded!');
}

// Setup analytics modal handler
// Removed duplicate DOMContentLoaded event listener to avoid conflicts

/* ===== BUYER OFFER/BID MANAGEMENT ===== */

// View buyer's offers
function openMyOffers() {
  if (!state.user) return alert("Login first.");
  
  const myOffers = state.offers.filter(o => o.buyer === state.user.email);
  
  if (myOffers.length === 0) {
    ModalManager.showAlert({
      title: 'My Offers',
      message: 'You have not made any offers yet.'
    });
    return;
  }
  
  // Create offers modal
  createMyOffersModal(myOffers);
  ModalManager.openModal('myOffersModal');
}

// View buyer's auction bids
function openMyBids() {
  if (!state.user) return alert("Login first.");
  
  const myBids = state.auctionBids.filter(b => b.bidder === state.user.email);
  
  if (myBids.length === 0) {
    ModalManager.showAlert({
      title: 'My Bids',
      message: 'You have not placed any auction bids yet.'
    });
    return;
  }
  
  // Create bids modal
  createMyBidsModal(myBids);
  ModalManager.openModal('myBidsModal');
}

// View buyer's contracts
function openMyContracts() {
  if (!state.user) return alert("Login first.");
  
  const myContracts = state.contracts.filter(c => c.buyer === state.user.email);
  
  if (myContracts.length === 0) {
    ModalManager.showAlert({
      title: 'My Contracts',
      message: 'You have no contracts yet.'
    });
    return;
  }
  
  // Create contracts modal
  createMyContractsModal(myContracts);
  ModalManager.openModal('myContractsModal');
}

// Create and display offers modal for buyers
function createMyOffersModal(offers) {
  let modal = $('#myOffersModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'myOffersModal';
    modal.className = 'modal hidden';
    document.body.appendChild(modal);
  }
  
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 900px;">
      <button class="modal-close">✕</button>
      <h3>📄 My Offers (${offers.length})</h3>
      
      <div class="offers-summary">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-value">${offers.filter(o => o.status === 'submitted').length}</div>
            <div class="stat-label">Pending</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${offers.filter(o => o.status === 'accepted').length}</div>
            <div class="stat-label">Accepted</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${offers.filter(o => o.status === 'rejected').length}</div>
            <div class="stat-label">Rejected</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${fmt(offers.reduce((sum, o) => sum + o.price, 0))}</div>
            <div class="stat-label">Total Value</div>
          </div>
        </div>
      </div>
      
      <div class="offers-list">
        ${offers.map(offer => {
          const listing = state.listings.find(l => l.id === offer.listingId);
          const statusClass = offer.status === 'accepted' ? 'success' : 
                             offer.status === 'rejected' ? 'danger' : 'warning';
          
          return `
            <div class="offer-card">
              <div class="offer-header">
                <div class="offer-property">
                  <h4>${listing ? listing.title : 'Unknown Property'}</h4>
                  <div class="offer-location">${listing ? listing.city + ', ' + listing.province : 'N/A'}</div>
                </div>
                <div class="offer-status ${statusClass}">
                  ${offer.status.toUpperCase()}
                </div>
              </div>
              
              <div class="offer-details">
                <div class="detail-grid">
                  <div><strong>Offer Price:</strong> ${fmt(offer.price)}</div>
                  <div><strong>Earnest Money:</strong> ${fmt(offer.earnestMoney)}</div>
                  <div><strong>Closing Date:</strong> ${new Date(offer.closingDate).toLocaleDateString()}</div>
                  <div><strong>Submitted:</strong> ${new Date(offer.submittedAt).toLocaleDateString()}</div>
                </div>
              </div>
              
              ${offer.contingencies ? `
                <div class="offer-contingencies">
                  <strong>Contingencies:</strong>
                  <div class="contingency-list">
                    ${offer.contingencies.financing.enabled ? `<span class="contingency-tag">Financing (${offer.contingencies.financing.days} days)</span>` : ''}
                    ${offer.contingencies.survey.enabled ? `<span class="contingency-tag">Survey (${offer.contingencies.survey.days} days)</span>` : ''}
                    ${offer.contingencies.title.enabled ? `<span class="contingency-tag">Title (${offer.contingencies.title.days} days)</span>` : ''}
                    ${offer.contingencies.environmental.enabled ? `<span class="contingency-tag">Environmental (${offer.contingencies.environmental.days} days)</span>` : ''}
                  </div>
                </div>
              ` : ''}
              
              <div class="offer-actions">
                <button class="btn btn-sm" onclick="viewOfferHistory(${offer.id})">📋 View History</button>
                ${offer.status === 'submitted' ? `<button class="btn btn-sm danger" onclick="withdrawOffer(${offer.id})">❌ Withdraw</button>` : ''}
                ${listing ? `<button class="btn btn-sm ghost" onclick="openListing(${listing.id})">🏠 View Property</button>` : ''}
              </div>
            </div>
          `;
        }).join('')}
      </div>
      
      <div class="modal-actions">
        <button class="btn ghost modal-close">Close</button>
        <button class="btn" onclick="exportOffers()">📊 Export Report</button>
      </div>
    </div>
  `;
  
  ModalManager.setupModalCloseButtons('myOffersModal');
}

// Create and display bids modal for buyers
function createMyBidsModal(bids) {
  let modal = $('#myBidsModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'myBidsModal';
    modal.className = 'modal hidden';
    document.body.appendChild(modal);
  }
  
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 800px;">
      <button class="modal-close">✕</button>
      <h3>🏗️ My Auction Bids (${bids.length})</h3>
      
      <div class="bids-summary">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-value">${bids.filter(b => b.status === 'active').length}</div>
            <div class="stat-label">Active</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${bids.filter(b => b.status === 'winning').length}</div>
            <div class="stat-label">Winning</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${bids.filter(b => b.status === 'outbid').length}</div>
            <div class="stat-label">Outbid</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${fmt(bids.reduce((sum, b) => Math.max(sum, b.amount), 0))}</div>
            <div class="stat-label">Highest Bid</div>
          </div>
        </div>
      </div>
      
      <div class="bids-list">
        ${bids.length === 0 ? '<div class="empty-state">No auction bids placed yet</div>' : 
          bids.map(bid => {
            const listing = state.listings.find(l => l.id === bid.listingId);
            const statusClass = bid.status === 'winning' ? 'success' : 
                               bid.status === 'outbid' ? 'danger' : 'warning';
            
            return `
              <div class="bid-card">
                <div class="bid-header">
                  <div class="bid-property">
                    <h4>${listing ? listing.title : 'Unknown Property'}</h4>
                    <div class="bid-location">${listing ? listing.city + ', ' + listing.province : 'N/A'}</div>
                  </div>
                  <div class="bid-status ${statusClass}">
                    ${bid.status.toUpperCase()}
                  </div>
                </div>
                
                <div class="bid-details">
                  <div class="detail-grid">
                    <div><strong>Your Bid:</strong> ${fmt(bid.amount)}</div>
                    <div><strong>Current High:</strong> ${listing && listing.auctionBids ? fmt(Math.max(...listing.auctionBids.map(b => b.amount))) : 'N/A'}</div>
                    <div><strong>Placed:</strong> ${new Date(bid.timestamp).toLocaleDateString()}</div>
                    <div><strong>Auction Ends:</strong> ${listing && listing.auctionEnds ? new Date(listing.auctionEnds).toLocaleDateString() : 'N/A'}</div>
                  </div>
                </div>
                
                <div class="bid-actions">
                  ${bid.status === 'active' && listing && listing.auctionEnds > Date.now() ? `<button class="btn btn-sm primary" onclick="increaseBid(${bid.id})">⬆️ Increase Bid</button>` : ''}
                  ${listing ? `<button class="btn btn-sm ghost" onclick="openListing(${listing.id})">🏠 View Property</button>` : ''}
                </div>
              </div>
            `;
          }).join('')
        }
      </div>
      
      <div class="modal-actions">
        <button class="btn ghost modal-close">Close</button>
        <button class="btn" onclick="exportBids()">📊 Export Report</button>
      </div>
    </div>
  `;
  
  ModalManager.setupModalCloseButtons('myBidsModal');
}

// Create and display contracts modal for buyers
function createMyContractsModal(contracts) {
  let modal = $('#myContractsModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'myContractsModal';
    modal.className = 'modal hidden';
    document.body.appendChild(modal);
  }
  
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 900px;">
      <button class="modal-close">✕</button>
      <h3>📋 My Contracts (${contracts.length})</h3>
      
      <div class="contracts-summary">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-value">${contracts.filter(c => c.status === 'generated').length}</div>
            <div class="stat-label">Pending</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${contracts.filter(c => c.status === 'partially_signed').length}</div>
            <div class="stat-label">Partial</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${contracts.filter(c => c.status === 'fully_executed').length}</div>
            <div class="stat-label">Complete</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${fmt(contracts.reduce((sum, c) => sum + c.purchasePrice, 0))}</div>
            <div class="stat-label">Total Value</div>
          </div>
        </div>
      </div>
      
      <div class="contracts-list">
        ${contracts.map(contract => {
          const listing = state.listings.find(l => l.id === contract.listingId);
          const statusClass = contract.status === 'fully_executed' ? 'success' : 
                             contract.status === 'partially_signed' ? 'warning' : 'info';
          const needsSignature = !contract.signatures.find(s => s.signer === state.user.email);
          
          return `
            <div class="contract-card">
              <div class="contract-header">
                <div class="contract-property">
                  <h4>Contract #${contract.id}</h4>
                  <div class="contract-title">${listing ? listing.title : 'Unknown Property'}</div>
                  <div class="contract-location">${listing ? listing.city + ', ' + listing.province : 'N/A'}</div>
                </div>
                <div class="contract-status ${statusClass}">
                  ${contract.status.replace('_', ' ').toUpperCase()}
                </div>
              </div>
              
              <div class="contract-details">
                <div class="detail-grid">
                  <div><strong>Purchase Price:</strong> ${fmt(contract.purchasePrice)}</div>
                  <div><strong>Earnest Money:</strong> ${fmt(contract.earnestMoney)}</div>
                  <div><strong>Closing Date:</strong> ${new Date(contract.closingDate).toLocaleDateString()}</div>
                  <div><strong>Generated:</strong> ${new Date(contract.generatedAt).toLocaleDateString()}</div>
                </div>
              </div>
              
              <div class="contract-signatures">
                <strong>Signatures (${contract.signaturesCollected}/${contract.signaturesRequired}):</strong>
                <div class="signature-list">
                  ${contract.signatures.map(sig => `
                    <div class="signature-item">
                      <span class="signature-role">${sig.role}:</span>
                      <span class="signature-name">${sig.signer}</span>
                      <span class="signature-date">${new Date(sig.signedAt).toLocaleDateString()}</span>
                    </div>
                  `).join('')}
                </div>
              </div>
              
              <div class="contract-actions">
                ${needsSignature && contract.status !== 'fully_executed' ? `<button class="btn btn-sm primary" onclick="signContract(${contract.id})">✍️ Sign Contract</button>` : ''}
                <button class="btn btn-sm" onclick="downloadContract(${contract.id})">📄 Download</button>
                ${listing ? `<button class="btn btn-sm ghost" onclick="openListing(${listing.id})">🏠 View Property</button>` : ''}
              </div>
            </div>
          `;
        }).join('')}
      </div>
      
      <div class="modal-actions">
        <button class="btn ghost modal-close">Close</button>
        <button class="btn" onclick="exportContracts()">📊 Export Report</button>
      </div>
    </div>
  `;
  
  ModalManager.setupModalCloseButtons('myContractsModal');
}

// View offers received by sellers/brokers
function openReceivedOffers() {
  if (!state.user) return alert("Login first.");
  
  // Get all offers on user's listings
  const myListings = state.listings.filter(l => l.owner === state.user.email);
  const receivedOffers = [];
  
  myListings.forEach(listing => {
    if (listing.offers && listing.offers.length > 0) {
      listing.offers.forEach(offer => {
        receivedOffers.push({
          ...offer,
          listingTitle: listing.title,
          listingId: listing.id
        });
      });
    }
  });
  
  if (receivedOffers.length === 0) {
    ModalManager.showAlert({
      title: 'Received Offers',
      message: 'No offers received on your listings yet.'
    });
    return;
  }
  
  // Create received offers modal
  createReceivedOffersModal(receivedOffers);
  ModalManager.openModal('receivedOffersModal');
}

// View bids received by sellers/brokers on auction listings
function openReceivedBids() {
  if (!state.user) return alert("Login first.");
  
  // Get all bids on user's auction listings
  const myAuctionListings = state.listings.filter(l => l.owner === state.user.email && l.type === 'auction');
  const receivedBids = [];
  
  myAuctionListings.forEach(listing => {
    if (listing.bids && listing.bids.length > 0) {
      listing.bids.forEach(bid => {
        receivedBids.push({
          ...bid,
          listingTitle: listing.title,
          listingId: listing.id
        });
      });
    }
  });
  
  // Also check global auction bids
  state.auctionBids.forEach(bid => {
    const listing = state.listings.find(l => l.id === bid.listingId && l.owner === state.user.email);
    if (listing) {
      receivedBids.push({
        ...bid,
        listingTitle: listing.title
      });
    }
  });
  
  if (receivedBids.length === 0) {
    ModalManager.showAlert({
      title: 'Received Bids',
      message: 'No bids received on your auction listings yet.'
    });
    return;
  }
  
  // Create received bids modal
  createReceivedBidsModal(receivedBids);
  ModalManager.openModal('receivedBidsModal');
}

// Create received offers modal for sellers/brokers
function createReceivedOffersModal(offers) {
  let modal = $('#receivedOffersModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'receivedOffersModal';
    modal.className = 'modal hidden';
    document.body.appendChild(modal);
  }
  
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 1000px;">
      <button class="modal-close">✕</button>
      <h3>📨 Offers Received (${offers.length})</h3>
      
      <div class="offers-summary">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-value">${offers.filter(o => o.status === 'submitted').length}</div>
            <div class="stat-label">Pending Review</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${offers.filter(o => o.status === 'accepted').length}</div>
            <div class="stat-label">Accepted</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${offers.filter(o => o.status === 'countered').length}</div>
            <div class="stat-label">Countered</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${fmt(offers.reduce((sum, o) => Math.max(sum, o.price), 0))}</div>
            <div class="stat-label">Highest Offer</div>
          </div>
        </div>
      </div>
      
      <div class="offers-list">
        ${offers.map(offer => {
          const statusClass = offer.status === 'accepted' ? 'success' : 
                             offer.status === 'rejected' ? 'danger' : 
                             offer.status === 'countered' ? 'warning' : 'info';
          
          return `
            <div class="received-offer-card">
              <div class="offer-header">
                <div class="offer-info">
                  <h4>${offer.listingTitle}</h4>
                  <div class="offer-buyer">From: ${offer.buyerName || offer.buyer}</div>
                  <div class="offer-date">Received: ${new Date(offer.submittedAt).toLocaleDateString()}</div>
                </div>
                <div class="offer-status ${statusClass}">
                  ${offer.status.toUpperCase()}
                </div>
              </div>
              
              <div class="offer-details">
                <div class="offer-amounts">
                  <div class="amount-item primary">
                    <span class="amount-label">Offer Price:</span>
                    <span class="amount-value">${fmt(offer.price)}</span>
                  </div>
                  <div class="amount-item">
                    <span class="amount-label">Earnest Money:</span>
                    <span class="amount-value">${fmt(offer.earnestMoney)}</span>
                  </div>
                  <div class="amount-item">
                    <span class="amount-label">Closing Date:</span>
                    <span class="amount-value">${new Date(offer.closingDate).toLocaleDateString()}</span>
                  </div>
                </div>
              </div>
              
              ${offer.contingencies ? `
                <div class="offer-contingencies">
                  <strong>Contingencies:</strong>
                  <div class="contingency-list">
                    ${offer.contingencies.financing.enabled ? `<span class="contingency-tag">Financing (${offer.contingencies.financing.days} days)</span>` : ''}
                    ${offer.contingencies.survey.enabled ? `<span class="contingency-tag">Survey (${offer.contingencies.survey.days} days)</span>` : ''}
                    ${offer.contingencies.title.enabled ? `<span class="contingency-tag">Title (${offer.contingencies.title.days} days)</span>` : ''}
                    ${offer.contingencies.environmental.enabled ? `<span class="contingency-tag">Environmental (${offer.contingencies.environmental.days} days)</span>` : ''}
                  </div>
                </div>
              ` : ''}
              
              ${offer.buyerComments ? `
                <div class="offer-comments">
                  <strong>Buyer Comments:</strong>
                  <p>${offer.buyerComments}</p>
                </div>
              ` : ''}
              
              <div class="offer-actions">
                ${offer.status === 'submitted' || offer.status === 'counter_submitted' ? `
                  <button class="btn btn-sm success" onclick="acceptReceivedOffer(${offer.id})">✅ Accept</button>
                  <button class="btn btn-sm warning" onclick="counterReceivedOffer(${offer.id})">🔄 Counter</button>
                  <button class="btn btn-sm danger" onclick="rejectReceivedOffer(${offer.id})">❌ Reject</button>
                ` : ''}
                <button class="btn btn-sm ghost" onclick="viewOfferHistory(${offer.id})">📋 History</button>
                <button class="btn btn-sm ghost" onclick="contactBuyer('${offer.buyer}')">💬 Message Buyer</button>
              </div>
            </div>
          `;
        }).join('')}
      </div>
      
      <div class="modal-actions">
        <button class="btn ghost modal-close">Close</button>
        <button class="btn" onclick="exportReceivedOffers()">📊 Export Report</button>
      </div>
    </div>
  `;
  
  ModalManager.setupModalCloseButtons('receivedOffersModal');
}

// Create received bids modal for sellers/brokers
function createReceivedBidsModal(bids) {
  let modal = $('#receivedBidsModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'receivedBidsModal';
    modal.className = 'modal hidden';
    document.body.appendChild(modal);
  }
  
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 900px;">
      <button class="modal-close">✕</button>
      <h3>🏗️ Auction Bids Received (${bids.length})</h3>
      
      <div class="bids-summary">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-value">${bids.filter(b => b.status === 'active').length}</div>
            <div class="stat-label">Active Bids</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${new Set(bids.map(b => b.bidder)).size}</div>
            <div class="stat-label">Unique Bidders</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${fmt(Math.max(...bids.map(b => b.amount), 0))}</div>
            <div class="stat-label">Highest Bid</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">${fmt(bids.reduce((sum, b) => sum + b.amount, 0))}</div>
            <div class="stat-label">Total Bid Value</div>
          </div>
        </div>
      </div>
      
      <div class="bids-list">
        ${bids.map(bid => {
          const statusClass = bid.status === 'winning' ? 'success' : 
                             bid.status === 'outbid' ? 'warning' : 'info';
          const isHighest = bid.amount === Math.max(...bids.filter(b => b.listingId === bid.listingId).map(b => b.amount));
          
          return `
            <div class="received-bid-card ${isHighest ? 'highest-bid' : ''}">
              <div class="bid-header">
                <div class="bid-info">
                  <h4>${bid.listingTitle}</h4>
                  <div class="bid-bidder">From: ${bid.bidderName || bid.bidder}</div>
                  <div class="bid-date">Placed: ${new Date(bid.timestamp).toLocaleDateString()}</div>
                </div>
                <div class="bid-status ${statusClass}">
                  ${isHighest ? '🏆 HIGHEST' : bid.status.toUpperCase()}
                </div>
              </div>
              
              <div class="bid-details">
                <div class="bid-amount-display">
                  <span class="bid-amount">${fmt(bid.amount)}</span>
                  ${isHighest ? '<span class="highest-indicator">Current High Bid</span>' : ''}
                </div>
              </div>
              
              <div class="bid-actions">
                <button class="btn btn-sm ghost" onclick="viewBidHistory(${bid.listingId})">📋 All Bids</button>
                <button class="btn btn-sm ghost" onclick="contactBuyer('${bid.bidder}')">💬 Message Bidder</button>
                <button class="btn btn-sm ghost" onclick="openListing(${bid.listingId})">🏠 View Auction</button>
              </div>
            </div>
          `;
        }).join('')}
      </div>
      
      <div class="modal-actions">
        <button class="btn ghost modal-close">Close</button>
        <button class="btn" onclick="exportReceivedBids()">📊 Export Report</button>
      </div>
    </div>
  `;
  
  ModalManager.setupModalCloseButtons('receivedBidsModal');
}

/* Surveyor upload report */
function openUploadReport(){
  const listingId = Number(prompt("Attach report to listing id:"));
  if (!listingId) return;
  const l = state.listings.find(x=>x.id===listingId);
  if (!l) return alert("Listing not found.");
  const reportName = prompt("Report file name (e.g., SitePlan-123.pdf):");
  if (!reportName) return;
  l.reports = l.reports || [];
  l.reports.push({ name: reportName, uploader: state.user.email, uploadedAt: new Date().toLocaleString() });
  notify(`Surveyor ${state.user.email} uploaded report "${reportName}" for listing ${listingId}`);
  renderListings();
}

/* Sign document (simple e-sign toggle) */
function signDocumentForListing(listingId){
  if (!state.user) return alert("Login to sign.");
  // push small record to user
  state.user.signedDocs = state.user.signedDocs || [];
  state.user.signedDocs.push({ listingId, doc: "PSA", signedAt: new Date().toLocaleString() });
  saveState();
  notify(`${state.user.email} signed PSA for listing ${listingId} (simulated).`);
  alert("Document signed (simulated).");
}

/* Signatures & compliance (demo helpers) */
function requireRole(roles, cb){
  // Removed role-based restrictions - all users can access all functionality
  if (!state.user) return alert("Please login.");
  cb();
}

/* ===== Land Selling Modal Functions ===== */
let currentSellingRole = null;

function openLandSellingModal() {
  // Reset modal state
  currentSellingRole = null;
  
  // Hide all forms
  $("#sellerForm").classList.add("hidden");
  $("#brokerForm").classList.add("hidden");
  $("#realtyForm").classList.add("hidden");
  $("#landSellingSubmit").classList.add("hidden");
  
  // Reset role selection
  $$(".role-card").forEach(card => card.classList.remove("selected"));
  
  // Show modal
  $("#landSellingModal").classList.remove("hidden");
  
  // Setup role selection handlers
  setupLandSellingHandlers();
}

function setupLandSellingHandlers() {
  // Role card selection
  $$(".role-card").forEach(card => {
    card.addEventListener("click", (e) => {
      const role = e.currentTarget.dataset.role;
      selectRole(role);
    });
  });
  
  // Modal close handlers
  $("#landSellingClose")?.addEventListener("click", closeLandSellingModal);
  $("#landSellingCancel")?.addEventListener("click", closeLandSellingModal);
  
  // Submit handler
  $("#landSellingSubmit")?.addEventListener("click", submitLandSelling);
}

function selectRole(role) {
  currentSellingRole = role;
  
  // Update UI - highlight selected card
  $$(".role-card").forEach(card => card.classList.remove("selected"));
  $(`.role-card[data-role="${role}"]`).classList.add("selected");
  
  // Hide all forms first
  $("#sellerForm").classList.add("hidden");
  $("#brokerForm").classList.add("hidden");
  $("#realtyForm").classList.add("hidden");
  
  // Show appropriate form
  if (role === "seller") {
    $("#sellerForm").classList.remove("hidden");
  } else if (role === "broker") {
    $("#brokerForm").classList.remove("hidden");
  } else if (role === "realty") {
    $("#realtyForm").classList.remove("hidden");
  }
  
  // Show submit button
  $("#landSellingSubmit").classList.remove("hidden");
}

function closeLandSellingModal() {
  $("#landSellingModal").classList.add("hidden");
  currentSellingRole = null;
}

function submitLandSelling() {
  if (!currentSellingRole) {
    alert("Please select your role first.");
    return;
  }
  
  // Validate and collect form data based on role
  let formData;
  
  if (currentSellingRole === "seller") {
    formData = validateAndCollectSellerData();
  } else if (currentSellingRole === "broker") {
    formData = validateAndCollectBrokerData();
  } else if (currentSellingRole === "realty") {
    formData = validateAndCollectRealtyData();
  }
  
  if (!formData) return; // Validation failed
  
  // Create listing
  const listing = createListingFromFormData(formData, currentSellingRole);
  state.listings.unshift(listing);
  
  // Update UI
  renderListings();
  closeLandSellingModal();
  
  // Notify
  notify(`Land listing submitted as ${currentSellingRole}: ${listing.title}`);
  alert(`Your land listing has been submitted for review. Listing ID: ${listing.id}`);
}

function validateAndCollectSellerData() {
  const location = $("#sellerLocation").value.trim();
  const landType = $("#sellerLandType").value;
  const hectares = parseFloat($("#sellerHectares").value || "0");
  const price = parseFloat($("#sellerPrice").value || "0");
  const description = $("#sellerDescription").value.trim();
  const contactName = $("#sellerContactName").value.trim();
  const contactPhone = $("#sellerContactPhone").value.trim();
  
  // Validation
  if (!location || !landType || !hectares || !price || !description || !contactName || !contactPhone) {
    alert("Please fill in all required fields.");
    return null;
  }
  
  return {
    location,
    landType,
    hectares,
    price,
    description,
    contactName,
    contactPhone,
    documents: $("#sellerDocuments").files
  };
}

function validateAndCollectBrokerData() {
  const companyName = $("#brokerCompanyName").value.trim();
  const license = $("#brokerLicense").value.trim();
  const areas = $("#brokerAreas").value.trim();
  const commission = parseFloat($("#brokerCommission").value || "0");
  const location = $("#brokerLocation").value.trim();
  const landType = $("#brokerLandType").value;
  const hectares = parseFloat($("#brokerHectares").value || "0");
  const price = parseFloat($("#brokerPrice").value || "0");
  const contactName = $("#brokerContactName").value.trim();
  const contactPhone = $("#brokerContactPhone").value.trim();
  const description = $("#brokerDescription").value.trim();
  
  // Validation
  if (!companyName || !license || !location || !landType || !hectares || !price || !contactName || !contactPhone || !description) {
    alert("Please fill in all required fields.");
    return null;
  }
  
  return {
    companyName,
    license,
    areas,
    commission,
    location,
    landType,
    hectares,
    price,
    contactName,
    contactPhone,
    description,
    documents: $("#brokerDocuments").files
  };
}

function validateAndCollectRealtyData() {
  const companyName = $("#realtyCompanyName").value.trim();
  const license = $("#realtyLicense").value.trim();
  const principalBroker = $("#realtyPrincipalBroker").value.trim();
  const years = parseInt($("#realtyYears").value || "0");
  const location = $("#realtyLocation").value.trim();
  const landType = $("#realtyLandType").value;
  const hectares = parseFloat($("#realtyHectares").value || "0");
  const price = parseFloat($("#realtyPrice").value || "0");
  const listedCount = parseInt($("#realtyListedCount").value || "0");
  const contactName = $("#realtyContactName").value.trim();
  const contactPhone = $("#realtyContactPhone").value.trim();
  const description = $("#realtyDescription").value.trim();
  
  // Validation
  if (!companyName || !license || !location || !landType || !hectares || !price || !contactName || !contactPhone || !description) {
    alert("Please fill in all required fields.");
    return null;
  }
  
  return {
    companyName,
    license,
    principalBroker,
    years,
    location,
    landType,
    hectares,
    price,
    listedCount,
    contactName,
    contactPhone,
    description,
    documents: $("#realtyDocuments").files
  };
}

/* ===== Land Selling Modal Functions ===== */
function openLandSellingModal() {
  // Reset modal state
  let currentSellingRole = "seller"; // Default to seller for all users
  
  // Show modal
  $("#landSellingModal").classList.remove("hidden");
  
  // Setup handlers
  setupLandSellingHandlers();
}

function setupLandSellingHandlers() {
  // Modal close handlers
  $("#landSellingClose")?.addEventListener("click", closeLandSellingModal);
  $("#landSellingCancel")?.addEventListener("click", closeLandSellingModal);
  
  // Submit handler
  $("#landSellingSubmit")?.addEventListener("click", submitLandSelling);
}

function closeLandSellingModal() {
  $("#landSellingModal").classList.add("hidden");
  currentSellingRole = null;
}

function submitLandSelling() {
  // Validate and collect form data
  const formData = validateAndCollectSellerData();
  
  if (!formData) return; // Validation failed
  
  // Create listing
  const listing = createListingFromFormData(formData, "seller");
  
  // Add to listings
  state.listings.unshift(listing);
  
  // Close modal
  closeLandSellingModal();
  
  // Update UI
  renderListings();
  notify(`Listing published: ${listing.title}`);
  
  // Save state
  saveState();
}

// Use the existing validation function for seller data
function validateAndCollectSellerData() {
  const location = $("#sellerLocation").value.trim();
  const landType = $("#sellerLandType").value;
  const hectares = parseFloat($("#sellerHectares").value || "0");
  const price = parseFloat($("#sellerPrice").value || "0");
  const description = $("#sellerDescription").value.trim();
  const contactName = $("#sellerContactName").value.trim();
  const contactPhone = $("#sellerContactPhone").value.trim();
  
  // Validation
  if (!location || !landType || !hectares || !price || !description || !contactName || !contactPhone) {
    alert("Please fill in all required fields.");
    return null;
  }
  
  return {
    location,
    landType,
    hectares,
    price,
    description,
    contactName,
    contactPhone,
    documents: $("#sellerDocuments").files
  };
}

// Remove the other validation functions as we're only using one form now

function createListingFromFormData(data, role) {
  // Convert hectares to square meters for consistency with existing system
  const areaInSqm = Math.round(data.hectares * 10000);
  
  // Parse location (basic parsing)
  const locationParts = data.location.split(',').map(s => s.trim());
  const barangay = locationParts[0] || "";
  const city = locationParts[1] || "";
  const province = locationParts[2] || "";
  
  // Determine region based on common provinces (simplified)
  let region = "Region VII"; // Default
  if (province.toLowerCase().includes("manila") || province.toLowerCase().includes("ncr")) {
    region = "NCR";
  } else if (province.toLowerCase().includes("cebu") || province.toLowerCase().includes("bohol")) {
    region = "Region VII";
  } else if (province.toLowerCase().includes("davao") || province.toLowerCase().includes("mindanao")) {
    region = "Region XI";
  }
  
  // Create title based on data
  const title = `${data.hectares}ha ${data.landType} land in ${city || barangay}`;
  
  // Create listing object
  const listing = {
    id: Date.now(),
    title: title,
    price: data.price,
    zoning: data.landType.charAt(0).toUpperCase() + data.landType.slice(1),
    area: areaInSqm,
    region: region,
    province: province,
    city: city,
    barangay: barangay,
    coords: "", // Would be filled by geocoding in real app
    desc: data.description,
    thumbnails: [],
    type: "sale",
    owner: state.user.email,
    reports: [],
    sellerRole: role,
    sellerInfo: {
      contactName: data.contactName,
      contactPhone: data.contactPhone,
      ...(role === "broker" && {
        companyName: data.companyName,
        license: data.license,
        areas: data.areas,
        commission: data.commission
      }),
      ...(role === "realty" && {
        companyName: data.companyName,
        license: data.license,
        principalBroker: data.principalBroker,
        years: data.years,
        listedCount: data.listedCount
      })
    },
    submittedAt: new Date().toLocaleString()
  };
  
  return listing;
}

/* simple document signing UI stub (already wired via signDocumentForListing) */

/* ===== COMPREHENSIVE MESSAGING SYSTEM ===== */
const MessageSystem = {
  conversations: [],
  currentConversation: null,
  messageIdCounter: 1,
  conversationIdCounter: 1,
  typingIndicators: new Map(),
  onlineUsers: new Set(),
  messageCache: new Map(),
  searchResults: [],
  currentSearchTerm: '',
  selectedMessages: new Set(),
  replyToMessage: null,
  
  // Message types
  MESSAGE_TYPES: {
    TEXT: 'text',
    FILE: 'file',
    IMAGE: 'image',
    LISTING_SHARE: 'listing_share',
    SYSTEM: 'system',
    OFFER: 'offer',
    APPOINTMENT: 'appointment'
  },
  
  // Message status
  MESSAGE_STATUS: {
    SENDING: 'sending',
    SENT: 'sent',
    DELIVERED: 'delivered',
    READ: 'read',
    FAILED: 'failed'
  },
  
  init() {
    console.log('Initializing MessageSystem');
    this.loadConversations();
    this.loadSettings();
    this.setupMessageHandlers();
    this.setupAdvancedHandlers();
    this.startTypingDetection();
    this.simulateOnlineStatus();
    this.setupAutoSave();
    console.log('📱 Comprehensive Messaging System initialized');
  },
  
  loadConversations() {
    const saved = localStorage.getItem('lts_conversations_v2');
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        this.conversations = parsed.conversations || [];
        this.messageIdCounter = parsed.messageIdCounter || 1;
        this.conversationIdCounter = parsed.conversationIdCounter || 1;
        
        // Migrate old format if needed
        this.conversations.forEach(conv => {
          if (!conv.settings) {
            conv.settings = {
              notifications: true,
              archived: false,
              muted: false,
              pinned: false
            };
          }
          conv.messages.forEach(msg => {
            if (!msg.status) msg.status = this.MESSAGE_STATUS.SENT;
            if (!msg.type) msg.type = this.MESSAGE_TYPES.TEXT;
            if (!msg.reactions) msg.reactions = [];
            if (!msg.edited) msg.edited = false;
          });
        });
      } catch (e) {
        console.warn('Error loading conversations:', e);
      }
    }
  },
  
  loadSettings() {
    const saved = localStorage.getItem('lts_messaging_settings');
    if (saved) {
      try {
        this.settings = JSON.parse(saved);
      } catch (e) {
        console.warn('Error loading settings:', e);
      }
    }
    
    // Default settings
    this.settings = this.settings || {
      notifications: true,
      soundNotifications: true,
      showOnlineStatus: true,
      showTypingIndicators: true,
      autoDownloadFiles: false,
      messagePreview: true,
      theme: 'light'
    };
  },
  
  saveConversations() {
    localStorage.setItem('lts_conversations_v2', JSON.stringify({
      conversations: this.conversations,
      messageIdCounter: this.messageIdCounter,
      conversationIdCounter: this.conversationIdCounter
    }));
    
    // Update message badge
    this.updateMessageBadge();
  },
  
  saveSettings() {
    localStorage.setItem('lts_messaging_settings', JSON.stringify(this.settings));
  },
  
  setupAutoSave() {
    setInterval(() => {
      this.saveConversations();
    }, 10000); // Auto-save every 10 seconds
  },
  
  setupMessageHandlers() {
    // Message Seller button in listing modal - using event delegation
    const listingModal = $('#listingModal');
    console.log('Setting up message handlers, listingModal exists:', !!listingModal);
    if (listingModal) {
      listingModal.addEventListener('click', (e) => {
        console.log('Click event on listingModal, target:', e.target);
        if (e.target && e.target.id === 'messageSellerBtn') {
          e.preventDefault();
          console.log('Message seller button clicked!');
          this.openMessagingFromListing();
        }
      });
    } else {
      console.log('ERROR: listingModal not found when setting up message handlers');
    }
    
    // New conversation button
    $('#newConversationBtn')?.addEventListener('click', () => {
      this.openNewConversationModal();
    });
    
    // Send message button
    $('#sendMessageBtn')?.addEventListener('click', () => {
      this.sendMessage();
    });
    
    // Enter key to send message
    $('#messageInput')?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.sendMessage();
      }
    });
    
    // Auto-resize textarea and typing detection
    $('#messageInput')?.addEventListener('input', (e) => {
      e.target.style.height = 'auto';
      e.target.style.height = e.target.scrollHeight + 'px';
      this.handleTyping();
    });
    
    // Conversation search
    $('#conversationSearch')?.addEventListener('input', (e) => {
      this.filterConversations(e.target.value);
    });
    
    // Start conversation button
    $('#startConversationBtn')?.addEventListener('click', () => {
      this.startNewConversation();
    });
  },
  
  setupAdvancedHandlers() {
    // File attachment handler
    $('#attachFileBtn')?.addEventListener('click', () => {
      this.showAttachmentOptions();
    });
    
    // Share listing handler
    $('#addListingBtn')?.addEventListener('click', () => {
      this.showListingPicker();
    });
    
    // Message context menu (right-click)
    document.addEventListener('contextmenu', (e) => {
      if (e.target.closest('.message-bubble')) {
        e.preventDefault();
        this.showMessageContextMenu(e);
      }
    });
    
    // Message selection (double-click)
    document.addEventListener('dblclick', (e) => {
      if (e.target.closest('.message-bubble')) {
        const messageEl = e.target.closest('.message');
        const messageId = messageEl.dataset.messageId;
        this.selectMessage(messageId);
      }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (ModalManager.isModalOpen('messagingModal')) {
        this.handleKeyboardShortcuts(e);
      }
    });
  },
  
  openMessagingFromListing() {
    console.log('openMessagingFromListing called');
    console.log('state.user:', state.user);
    console.log('state.currentListing:', state.currentListing);
    
    if (!state.user) {
      ModalManager.showAlert({ 
        title: 'Login Required', 
        message: 'Please login to send messages.' 
      });
      return;
    }
    
    if (!state.currentListing) return;
    
    // Find or create conversation with listing owner
    const ownerEmail = state.currentListing.owner;
    if (ownerEmail === state.user.email) {
      ModalManager.showAlert({ 
        title: 'Cannot Message', 
        message: 'You cannot message yourself about your own listing.' 
      });
      return;
    }
    
    let conversation = this.conversations.find(c => 
      (c.participants.includes(state.user.email) && c.participants.includes(ownerEmail)) ||
      (c.listingId === state.currentListing.id)
    );
    
    if (!conversation) {
      conversation = this.createConversation(ownerEmail, {
        listingId: state.currentListing.id,
        initialMessage: `Hi! I'm interested in your property: ${state.currentListing.title}`
      });
    }
    
    this.openMessagingModal(conversation.id);
  },
  
  openMessagingModal(conversationId = null) {
    ModalManager.openModal('messagingModal', {
      onOpen: () => {
        this.populateAvailableUsers();
        this.renderConversations();
        if (conversationId) {
          this.selectConversation(conversationId);
        } else {
          this.showEmptyChat();
        }
      }
    });
  },
  
  openNewConversationModal() {
    this.populateAvailableUsers();
    ModalManager.openModal('newConversationModal', { stack: true });
  },
  
  populateAvailableUsers() {
    const select = $('#newConversationUser');
    if (!select || !state.user) return;
    
    // Get all unique users from listings and registered users
    const availableUsers = new Set();
    
    // Add listing owners
    state.listings.forEach(listing => {
      if (listing.owner !== state.user.email) {
        availableUsers.add(listing.owner);
      }
    });
    
    // Add registered users
    state.users.forEach(user => {
      if (user.email !== state.user.email) {
        availableUsers.add(user.email);
      }
    });
    
    select.innerHTML = '<option value="">Select a user...</option>' +
      Array.from(availableUsers).map(email => {
        const user = state.users.find(u => u.email === email);
        const displayName = user ? `${user.name} (${user.role})` : email;
        return `<option value="${email}">${displayName}</option>`;
      }).join('');
  },
  
  createConversation(recipientEmail, options = {}) {
    const conversation = {
      id: this.conversationIdCounter++,
      participants: [state.user.email, recipientEmail],
      messages: [],
      createdAt: Date.now(),
      lastActivity: Date.now(),
      listingId: options.listingId || null,
      unreadCount: { [state.user.email]: 0, [recipientEmail]: 0 }
    };
    
    // Add initial message if provided
    if (options.initialMessage) {
      const message = {
        id: this.messageIdCounter++,
        conversationId: conversation.id,
        senderId: state.user.email,
        content: options.initialMessage,
        timestamp: Date.now(),
        type: this.MESSAGE_TYPES.TEXT,
        status: this.MESSAGE_STATUS.SENT,
        reactions: [],
        edited: false,
        read: false
      };
      conversation.messages.push(message);
      conversation.unreadCount[recipientEmail] = 1;
    }
    
    this.conversations.push(conversation);
    this.saveConversations();
    return conversation;
  },
  
  startNewConversation() {
    const recipientEmail = $('#newConversationUser').value.trim();
    const messageContent = $('#newConversationMessage').value.trim();
    
    if (!recipientEmail) {
      ModalManager.showAlert({ 
        title: 'Select User', 
        message: 'Please select a user to start conversation with.' 
      });
      return;
    }
    
    // Check if conversation already exists
    let conversation = this.conversations.find(c => 
      c.participants.includes(state.user.email) && 
      c.participants.includes(recipientEmail)
    );
    
    if (conversation) {
      ModalManager.closeModal('newConversationModal');
      this.selectConversation(conversation.id);
      if (messageContent) {
        $('#messageInput').value = messageContent;
      }
      return;
    }
    
    // Create new conversation
    conversation = this.createConversation(recipientEmail, {
      initialMessage: messageContent || `Hi! I'd like to connect with you on TerraTrade.`
    });
    
    ModalManager.closeModal('newConversationModal');
    this.selectConversation(conversation.id);
    notify(`Started conversation with ${recipientEmail}`);
  },
  
  renderConversations() {
    const container = $('#conversationsList');
    if (!container || !state.user) return;
    
    const userConversations = this.conversations
      .filter(c => c.participants.includes(state.user.email))
      .sort((a, b) => b.lastActivity - a.lastActivity);
    
    if (userConversations.length === 0) {
      container.innerHTML = `
        <div style="text-align: center; padding: 20px; color: #999;">
          <p>No conversations yet</p>
          <p style="font-size: 12px;">Start a conversation by browsing listings</p>
        </div>
      `;
      return;
    }
    
    container.innerHTML = userConversations.map(conversation => {
      const otherParticipant = conversation.participants.find(p => p !== state.user.email);
      const otherUser = state.users.find(u => u.email === otherParticipant) || 
        { email: otherParticipant, name: otherParticipant.split('@')[0], role: 'user' };
      
      const lastMessage = conversation.messages[conversation.messages.length - 1];
      const unreadCount = conversation.unreadCount[state.user.email] || 0;
      
      let preview = 'No messages yet';
      let timeStr = '';
      
      if (lastMessage) {
        preview = lastMessage.content.substring(0, 50) + (lastMessage.content.length > 50 ? '...' : '');
        timeStr = this.formatTime(lastMessage.timestamp);
      }
      
      return `
        <div class="conversation-item" data-conversation-id="${conversation.id}">
          <div class="conversation-avatar">${this.getAvatarEmoji(otherUser.role)}</div>
          <div class="conversation-info">
            <div class="conversation-name">${otherUser.name}</div>
            <div class="conversation-preview">${preview}</div>
          </div>
          <div class="conversation-meta">
            <div class="conversation-time">${timeStr}</div>
            ${unreadCount > 0 ? `<div class="conversation-unread">${unreadCount}</div>` : ''}
          </div>
        </div>
      `;
    }).join('');
    
    // Add click handlers
    container.querySelectorAll('.conversation-item').forEach(item => {
      item.addEventListener('click', (e) => {
        const conversationId = parseInt(e.currentTarget.dataset.conversationId);
        this.selectConversation(conversationId);
      });
    });
  },
  
  selectConversation(conversationId) {
    const conversation = this.conversations.find(c => c.id === conversationId);
    if (!conversation) return;
    
    this.currentConversation = conversation;
    
    // Mark messages as read
    conversation.messages.forEach(msg => {
      if (msg.senderId !== state.user.email) {
        msg.read = true;
      }
    });
    conversation.unreadCount[state.user.email] = 0;
    this.saveConversations();
    
    // Update UI
    this.renderConversations(); // Refresh to remove unread count
    this.showChatHeader(conversation);
    this.renderMessages(conversation);
    this.showChatInput();
    
    // Update active conversation styling
    document.querySelectorAll('.conversation-item').forEach(item => {
      item.classList.remove('active');
    });
    const activeItem = document.querySelector(`[data-conversation-id="${conversationId}"]`);
    if (activeItem) {
      activeItem.classList.add('active');
    }
  },
  
  showChatHeader(conversation) {
    const header = $('#chatHeader');
    if (!header) return;
    
    const otherParticipant = conversation.participants.find(p => p !== state.user.email);
    const otherUser = state.users.find(u => u.email === otherParticipant) || 
      { email: otherParticipant, name: otherParticipant.split('@')[0], role: 'user' };
    
    header.querySelector('.chat-user-name').textContent = otherUser.name;
    header.querySelector('.chat-user-role').textContent = otherUser.role || 'User';
    header.querySelector('.chat-user-avatar').textContent = this.getAvatarEmoji(otherUser.role);
    header.classList.remove('hidden');
  },
  
  showChatInput() {
    $('#chatInput')?.classList.remove('hidden');
  },
  
  showEmptyChat() {
    $('#chatHeader')?.classList.add('hidden');
    $('#chatInput')?.classList.add('hidden');
    const messages = $('#chatMessages');
    if (messages) {
      messages.innerHTML = `
        <div class="chat-empty">
          <div class="chat-empty-icon">💬</div>
          <h4>Select a conversation</h4>
          <p class="muted">Choose a conversation to start messaging</p>
        </div>
      `;
    }
  },
  
  renderMessages(conversation) {
    const container = $('#chatMessages');
    if (!container) return;
    
    if (conversation.messages.length === 0) {
      container.innerHTML = `
        <div class="chat-empty">
          <div class="chat-empty-icon">💬</div>
          <h4>Start the conversation</h4>
          <p class="muted">Send a message to get started</p>
        </div>
      `;
      return;
    }
    
    container.innerHTML = conversation.messages.map(message => {
      const sender = state.users.find(u => u.email === message.senderId) || 
        { email: message.senderId, name: message.senderId.split('@')[0], role: 'user' };
      const isOwn = message.senderId === state.user.email;
      
      // Generate message content based on type
      let messageContent = '';
      let replyContent = '';
      
      // Handle reply messages
      if (message.replyTo) {
        const originalMsg = this.findMessage(message.replyTo);
        const originalSender = originalMsg ? (state.users.find(u => u.email === originalMsg.senderId) || 
          { name: originalMsg.senderId.split('@')[0] }) : { name: 'Unknown' };
        replyContent = `
          <div class="message-reply">
            <div class="reply-header">Replying to ${originalSender.name}</div>
            <div class="reply-content">${originalMsg ? this.escapeHtml(originalMsg.content.substring(0, 100)) : 'Message not found'}${originalMsg && originalMsg.content.length > 100 ? '...' : ''}</div>
          </div>
        `;
      }
      
      // Handle different message types
      if (message.type === this.MESSAGE_TYPES.FILE || message.type === this.MESSAGE_TYPES.IMAGE) {
        messageContent = `
          <div class="message-file">
            <div class="file-icon">${message.type === this.MESSAGE_TYPES.IMAGE ? '🖼️' : '📎'}</div>
            <div class="file-info">
              <div class="file-name">${message.fileData?.name || 'Unknown file'}</div>
              <div class="file-size">${message.fileData?.size ? this.formatFileSize(message.fileData.size) : ''}</div>
            </div>
            ${message.fileData?.url && message.type === this.MESSAGE_TYPES.IMAGE ? 
              `<img src="${message.fileData.url}" alt="${message.fileData.name}" style="max-width: 200px; border-radius: 8px; margin-top: 8px;">` : ''}
          </div>
        `;
      } else if (message.type === this.MESSAGE_TYPES.LISTING_SHARE) {
        messageContent = `
          <div class="message-listing">
            <div class="listing-preview">
              <div class="listing-thumb">🏠</div>
              <div class="listing-details">
                <div class="listing-title">${message.listingData?.title || 'Property'}</div>
                <div class="listing-price">${message.listingData?.price ? fmt(message.listingData.price) : ''}</div>
                <div class="listing-meta">${message.listingData?.area || ''} sqm</div>
              </div>
            </div>
          </div>
        `;
      } else {
        messageContent = `<div class="message-bubble ${message.edited ? 'edited' : ''}">${this.escapeHtml(message.content)}</div>`;
      }
      
      // Message reactions
      let reactionsHtml = '';
      if (message.reactions && message.reactions.length > 0) {
        const reactionCounts = {};
        message.reactions.forEach(reaction => {
          reactionCounts[reaction.emoji] = reactionCounts[reaction.emoji] || [];
          reactionCounts[reaction.emoji].push(reaction.userId);
        });
        
        reactionsHtml = `
          <div class="message-reactions">
            ${Object.entries(reactionCounts).map(([emoji, users]) => {
              const hasOwnReaction = users.includes(state.user.email);
              return `<span class="message-reaction ${hasOwnReaction ? 'own' : ''}" data-emoji="${emoji}">${emoji} ${users.length}</span>`;
            }).join('')}
          </div>
        `;
      }
      
      // Status indicators for own messages
      let statusIndicator = '';
      if (isOwn && message.status) {
        const statusIcons = {
          [this.MESSAGE_STATUS.SENDING]: '🕐',
          [this.MESSAGE_STATUS.SENT]: '✓',
          [this.MESSAGE_STATUS.DELIVERED]: '✓✓',
          [this.MESSAGE_STATUS.READ]: '✓✓'
        };
        statusIndicator = `<span class="message-status ${message.status}" title="${message.status}">${statusIcons[message.status] || ''}</span>`;
      }
      
      return `
        <div class="message ${isOwn ? 'own' : ''}" data-message-id="${message.id}">
          <div class="message-avatar">${this.getAvatarEmoji(sender.role)}</div>
          <div class="message-content">
            ${replyContent}
            ${messageContent}
            ${reactionsHtml}
            <div class="message-time">
              ${this.formatTime(message.timestamp)}
              ${statusIndicator}
              ${message.edited ? '<span class="edited-indicator" title="Edited">(edited)</span>' : ''}
            </div>
          </div>
        </div>
      `;
    }).join('');
    
    // Scroll to bottom
    container.scrollTop = container.scrollHeight;
  },
  
  sendMessage() {
    if (!this.currentConversation) return;
    
    const input = $('#messageInput');
    const content = input.value.trim();
    if (!content) return;
    
    const message = {
      id: this.messageIdCounter++,
      conversationId: this.currentConversation.id,
      senderId: state.user.email,
      content: content,
      timestamp: Date.now(),
      type: this.MESSAGE_TYPES.TEXT,
      status: this.MESSAGE_STATUS.SENT,
      reactions: [],
      edited: false,
      read: false
    };
    
    // Add reply reference if replying to a message
    if (this.replyToMessage) {
      message.replyTo = this.replyToMessage.id;
      this.clearReply();
    }
    
    this.currentConversation.messages.push(message);
    this.currentConversation.lastActivity = Date.now();
    
    // Update unread count for other participants
    this.currentConversation.participants.forEach(participant => {
      if (participant !== state.user.email) {
        this.currentConversation.unreadCount[participant] = 
          (this.currentConversation.unreadCount[participant] || 0) + 1;
      }
    });
    
    this.saveConversations();
    
    // Update UI
    input.value = '';
    input.style.height = 'auto';
    this.renderMessages(this.currentConversation);
    this.renderConversations(); // Refresh conversation list
    
    // Simulate notification to other party
    const otherParticipant = this.currentConversation.participants.find(p => p !== state.user.email);
    notify(`Message sent to ${otherParticipant}`);
  },
  
  filterConversations(searchTerm) {
    const container = $('#conversationsList');
    if (!container) return;
    
    const items = container.querySelectorAll('.conversation-item');
    items.forEach(item => {
      const name = item.querySelector('.conversation-name').textContent.toLowerCase();
      const preview = item.querySelector('.conversation-preview').textContent.toLowerCase();
      const matches = name.includes(searchTerm.toLowerCase()) || 
                     preview.includes(searchTerm.toLowerCase());
      item.style.display = matches ? 'flex' : 'none';
    });
  },
  
  getAvatarEmoji(role) {
    const roleEmojis = {
      buyer: '👤',
      seller: '🏡',
      broker: '🤝',
      realty: '🏢'
    };
    return roleEmojis[role] || '👤';
  },
  
  formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'now';
    if (diffMins < 60) return `${diffMins}m`;
    if (diffHours < 24) return `${diffHours}h`;
    if (diffDays < 7) return `${diffDays}d`;
    
    return date.toLocaleDateString();
  },
  
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  },
  
  // Advanced Features Implementation
  
  startTypingDetection() {
    let typingTimeout;
    
    $('#messageInput')?.addEventListener('input', () => {
      if (!this.currentConversation) return;
      
      clearTimeout(typingTimeout);
      
      // Show typing indicator
      this.showTypingIndicator(state.user.email);
      
      // Hide typing indicator after 2 seconds of no typing
      typingTimeout = setTimeout(() => {
        this.hideTypingIndicator(state.user.email);
      }, 2000);
    });
  },
  
  handleTyping() {
    if (!this.currentConversation || !this.settings.showTypingIndicators) return;
    
    const otherParticipant = this.currentConversation.participants.find(p => p !== state.user.email);
    if (otherParticipant) {
      this.simulateTypingIndicator(otherParticipant);
    }
  },
  
  showTypingIndicator(userId) {
    if (!this.settings.showTypingIndicators) return;
    
    const container = $('#chatMessages');
    const existingIndicator = container?.querySelector(`[data-typing="${userId}"]`);
    
    if (!existingIndicator && container) {
      const indicator = document.createElement('div');
      indicator.className = 'typing-indicator';
      indicator.dataset.typing = userId;
      indicator.innerHTML = `
        <div class="typing-dots">
          <span></span><span></span><span></span>
        </div>
        <span class="typing-text">typing...</span>
      `;
      container.appendChild(indicator);
      container.scrollTop = container.scrollHeight;
    }
  },
  
  hideTypingIndicator(userId) {
    const indicator = $('#chatMessages')?.querySelector(`[data-typing="${userId}"]`);
    if (indicator) {
      indicator.remove();
    }
  },
  
  simulateTypingIndicator(userId) {
    // Simulate other user typing (for demo purposes)
    if (Math.random() < 0.3) { // 30% chance
      this.showTypingIndicator(userId);
      setTimeout(() => {
        this.hideTypingIndicator(userId);
      }, 2000 + Math.random() * 3000);
    }
  },
  
  simulateOnlineStatus() {
    // Simulate online users (for demo)
    const updateOnlineUsers = () => {
      this.onlineUsers.clear();
      state.users.forEach(user => {
        if (Math.random() > 0.3) { // 70% chance of being online
          this.onlineUsers.add(user.email);
        }
      });
      this.updateOnlineStatusDisplay();
    };
    
    updateOnlineUsers();
    setInterval(updateOnlineUsers, 30000); // Update every 30 seconds
  },
  
  updateOnlineStatusDisplay() {
    if (!this.settings.showOnlineStatus) return;
    
    // Update online status in conversation list
    document.querySelectorAll('.conversation-item').forEach(item => {
      const conversationId = item.dataset.conversationId;
      const conversation = this.conversations.find(c => c.id == conversationId);
      if (conversation) {
        const otherParticipant = conversation.participants.find(p => p !== state.user.email);
        const avatar = item.querySelector('.conversation-avatar');
        if (avatar && this.onlineUsers.has(otherParticipant)) {
          avatar.classList.add('online');
        } else if (avatar) {
          avatar.classList.remove('online');
        }
      }
    });
    
    // Update online status in chat header
    const chatHeader = $('#chatHeader');
    if (chatHeader && this.currentConversation) {
      const otherParticipant = this.currentConversation.participants.find(p => p !== state.user.email);
      const avatar = chatHeader.querySelector('.chat-user-avatar');
      if (avatar && this.onlineUsers.has(otherParticipant)) {
        avatar.classList.add('online');
      } else if (avatar) {
        avatar.classList.remove('online');
      }
    }
  },
  
  showAttachmentOptions() {
    ModalManager.showConfirm({
      title: 'Attach File',
      message: 'What would you like to attach?',
      confirmText: 'Browse Files',
      cancelText: 'Cancel',
      onConfirm: () => {
        this.openFileSelector();
      }
    });
  },
  
  openFileSelector() {
    const input = document.createElement('input');
    input.type = 'file';
    input.multiple = true;
    input.accept = 'image/*,.pdf,.doc,.docx,.txt';
    
    input.addEventListener('change', (e) => {
      const files = Array.from(e.target.files);
      files.forEach(file => {
        this.sendFileMessage(file);
      });
    });
    
    input.click();
  },
  
  sendFileMessage(file) {
    if (!this.currentConversation) return;
    
    const message = {
      id: this.messageIdCounter++,
      conversationId: this.currentConversation.id,
      senderId: state.user.email,
      content: `📎 ${file.name} (${this.formatFileSize(file.size)})`,
      timestamp: Date.now(),
      type: file.type.startsWith('image/') ? this.MESSAGE_TYPES.IMAGE : this.MESSAGE_TYPES.FILE,
      status: this.MESSAGE_STATUS.SENDING,
      fileData: {
        name: file.name,
        size: file.size,
        type: file.type,
        url: URL.createObjectURL(file) // In real app, this would be uploaded to server
      },
      read: false
    };
    
    this.currentConversation.messages.push(message);
    this.currentConversation.lastActivity = Date.now();
    
    // Simulate upload progress
    setTimeout(() => {
      message.status = this.MESSAGE_STATUS.SENT;
      this.renderMessages(this.currentConversation);
      this.saveConversations();
    }, 1000 + Math.random() * 2000);
    
    this.renderMessages(this.currentConversation);
    notify(`File sent: ${file.name}`);
  },
  
  showListingPicker() {
    if (!state.listings.length) {
      ModalManager.showAlert({
        title: 'No Listings',
        message: 'No listings available to share.'
      });
      return;
    }
    
    // Create dynamic modal for listing selection
    const modal = document.createElement('div');
    modal.id = 'listingPickerModal';
    modal.className = 'modal';
    modal.innerHTML = `
      <div class="modal-content">
        <button class="modal-close">✕</button>
        <h3>Share a Listing</h3>
        <div class="listing-picker-grid">
          ${state.listings.map(listing => `
            <div class="listing-picker-item" data-listing-id="${listing.id}">
              <div class="listing-thumb">🏠</div>
              <div class="listing-info">
                <div class="listing-title">${listing.title}</div>
                <div class="listing-price">${fmt(listing.price)}</div>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add event listeners
    modal.querySelector('.modal-close').addEventListener('click', () => {
      modal.remove();
    });
    
    modal.querySelectorAll('.listing-picker-item').forEach(item => {
      item.addEventListener('click', () => {
        const listingId = parseInt(item.dataset.listingId);
        this.sendListingMessage(listingId);
        modal.remove();
      });
    });
    
    modal.classList.remove('hidden');
  },
  
  sendListingMessage(listingId) {
    const listing = state.listings.find(l => l.id === listingId);
    if (!listing || !this.currentConversation) return;
    
    const message = {
      id: this.messageIdCounter++,
      conversationId: this.currentConversation.id,
      senderId: state.user.email,
      content: `🏠 Shared listing: ${listing.title}\n${fmt(listing.price)} • ${listing.area} sqm\n${listing.desc}`,
      timestamp: Date.now(),
      type: this.MESSAGE_TYPES.LISTING_SHARE,
      status: this.MESSAGE_STATUS.SENT,
      listingData: {
        id: listing.id,
        title: listing.title,
        price: listing.price,
        area: listing.area,
        desc: listing.desc
      },
      read: false
    };
    
    this.currentConversation.messages.push(message);
    this.currentConversation.lastActivity = Date.now();
    this.saveConversations();
    
    this.renderMessages(this.currentConversation);
    this.renderConversations();
    
    notify(`Listing shared: ${listing.title}`);
  },
  
  showMessageContextMenu(event) {
    const messageEl = event.target.closest('.message');
    const messageId = parseInt(messageEl.dataset.messageId);
    const message = this.findMessage(messageId);
    
    if (!message) return;
    
    // Create context menu
    const contextMenu = document.createElement('div');
    contextMenu.className = 'message-context-menu';
    contextMenu.style.position = 'fixed';
    contextMenu.style.left = event.clientX + 'px';
    contextMenu.style.top = event.clientY + 'px';
    contextMenu.style.zIndex = '10000';
    
    const isOwnMessage = message.senderId === state.user.email;
    
    contextMenu.innerHTML = `
      <div class="context-menu-item" data-action="copy">📋 Copy</div>
      <div class="context-menu-item" data-action="reply">↩️ Reply</div>
      <div class="context-menu-item" data-action="react">😊 React</div>
      ${isOwnMessage ? '<div class="context-menu-item" data-action="edit">✏️ Edit</div>' : ''}
      ${isOwnMessage ? '<div class="context-menu-item" data-action="delete">🗑️ Delete</div>' : ''}
      <div class="context-menu-item" data-action="info">ℹ️ Info</div>
    `;
    
    document.body.appendChild(contextMenu);
    
    // Handle menu actions
    contextMenu.addEventListener('click', (e) => {
      const action = e.target.dataset.action;
      if (action) {
        this.handleMessageAction(action, message);
      }
      contextMenu.remove();
    });
    
    // Remove menu on outside click
    setTimeout(() => {
      document.addEventListener('click', () => {
        if (contextMenu.parentNode) {
          contextMenu.remove();
        }
      }, { once: true });
    }, 100);
  },
  
  handleMessageAction(action, message) {
    switch (action) {
      case 'copy':
        navigator.clipboard.writeText(message.content);
        notify('Message copied to clipboard');
        break;
      case 'reply':
        this.replyToMessage = message;
        this.showReplyIndicator(message);
        $('#messageInput').focus();
        break;
      case 'react':
        this.showReactionPicker(message);
        break;
      case 'edit':
        this.editMessage(message);
        break;
      case 'delete':
        this.deleteMessage(message);
        break;
      case 'info':
        this.showMessageInfo(message);
        break;
    }
  },
  
  showReactionPicker(message) {
    const reactions = ['👍', '❤️', '😂', '😮', '😢', '😡'];
    
    ModalManager.showConfirm({
      title: 'Add Reaction',
      message: reactions.map(emoji => `<button class="reaction-btn" data-reaction="${emoji}">${emoji}</button>`).join(''),
      confirmText: 'Cancel',
      cancelText: '',
      onConfirm: () => {}
    });
    
    // Handle reaction selection
    document.querySelectorAll('.reaction-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const reaction = btn.dataset.reaction;
        this.addReaction(message, reaction);
        ModalManager.closeModal('confirmModal');
      });
    });
  },
  
  addReaction(message, emoji) {
    if (!message.reactions) message.reactions = [];
    
    const existingReaction = message.reactions.find(r => 
      r.emoji === emoji && r.userId === state.user.email
    );
    
    if (existingReaction) {
      // Remove reaction if already exists
      message.reactions = message.reactions.filter(r => r !== existingReaction);
    } else {
      // Add new reaction
      message.reactions.push({
        emoji,
        userId: state.user.email,
        timestamp: Date.now()
      });
    }
    
    this.saveConversations();
    this.renderMessages(this.currentConversation);
  },
  
  editMessage(message) {
    const newContent = prompt('Edit message:', message.content);
    if (newContent && newContent !== message.content) {
      message.content = newContent;
      message.edited = true;
      message.editedAt = Date.now();
      
      this.saveConversations();
      this.renderMessages(this.currentConversation);
      notify('Message edited');
    }
  },
  
  deleteMessage(message) {
    ModalManager.showConfirm({
      title: 'Delete Message',
      message: 'Are you sure you want to delete this message?',
      confirmText: 'Delete',
      cancelText: 'Cancel',
      onConfirm: () => {
        const conversation = this.conversations.find(c => c.id === message.conversationId);
        if (conversation) {
          conversation.messages = conversation.messages.filter(m => m.id !== message.id);
          this.saveConversations();
          this.renderMessages(conversation);
          notify('Message deleted');
        }
      }
    });
  },
  
  showMessageInfo(message) {
    const sender = state.users.find(u => u.email === message.senderId) || 
      { email: message.senderId, name: message.senderId.split('@')[0] };
    
    ModalManager.showAlert({
      title: 'Message Info',
      message: `
        From: ${sender.name} (${sender.email})<br>
        Sent: ${new Date(message.timestamp).toLocaleString()}<br>
        Status: ${message.status}<br>
        ${message.edited ? `Edited: ${new Date(message.editedAt).toLocaleString()}` : ''}
      `
    });
  },
  
  handleKeyboardShortcuts(event) {
    if (event.ctrlKey || event.metaKey) {
      switch (event.key.toLowerCase()) {
        case 'f':
          event.preventDefault();
          this.showMessageSearch();
          break;
        case 'n':
          event.preventDefault();
          this.openNewConversationModal();
          break;
        case 'k':
          event.preventDefault();
          this.focusConversationSearch();
          break;
      }
    }
    
    if (event.key === 'Escape') {
      this.clearSelection();
      this.clearReply();
    }
  },
  
  showMessageSearch() {
    // Enhanced search functionality
    const searchTerm = prompt('Search messages:');
    if (!searchTerm) return;
    
    this.searchMessages(searchTerm);
  },
  
  searchMessages(term) {
    this.currentSearchTerm = term.toLowerCase();
    this.searchResults = [];
    
    this.conversations.forEach(conv => {
      conv.messages.forEach(msg => {
        if (msg.content.toLowerCase().includes(this.currentSearchTerm)) {
          this.searchResults.push({
            conversationId: conv.id,
            messageId: msg.id,
            message: msg
          });
        }
      });
    });
    
    this.showSearchResults();
  },
  
  showSearchResults() {
    if (this.searchResults.length === 0) {
      ModalManager.showAlert({
        title: 'Search Results',
        message: 'No messages found matching your search.'
      });
      return;
    }
    
    // Create search results modal
    const modal = document.createElement('div');
    modal.id = 'searchResultsModal';
    modal.className = 'modal';
    modal.innerHTML = `
      <div class="modal-content">
        <button class="modal-close">✕</button>
        <h3>Search Results (${this.searchResults.length})</h3>
        <div class="search-results-list">
          ${this.searchResults.map(result => {
            const conv = this.conversations.find(c => c.id === result.conversationId);
            const otherParticipant = conv.participants.find(p => p !== state.user.email);
            const user = state.users.find(u => u.email === otherParticipant) || 
              { name: otherParticipant.split('@')[0] };
            
            return `
              <div class="search-result-item" data-conversation-id="${result.conversationId}" data-message-id="${result.messageId}">
                <div class="result-user">${user.name}</div>
                <div class="result-message">${this.highlightSearchTerm(result.message.content)}</div>
                <div class="result-time">${this.formatTime(result.message.timestamp)}</div>
              </div>
            `;
          }).join('')}
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add event listeners
    modal.querySelector('.modal-close').addEventListener('click', () => {
      modal.remove();
    });
    
    modal.querySelectorAll('.search-result-item').forEach(item => {
      item.addEventListener('click', () => {
        const conversationId = parseInt(item.dataset.conversationId);
        this.selectConversation(conversationId);
        modal.remove();
      });
    });
  },
  
  highlightSearchTerm(text) {
    if (!this.currentSearchTerm) return text;
    
    const regex = new RegExp(`(${this.currentSearchTerm})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
  },
  
  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  },
  
  findMessage(messageId) {
    for (const conv of this.conversations) {
      const message = conv.messages.find(m => m.id === messageId);
      if (message) return message;
    }
    return null;
  },
  
  // Conversation Management
  archiveConversation(conversationId) {
    const conversation = this.conversations.find(c => c.id === conversationId);
    if (conversation) {
      conversation.settings.archived = true;
      this.saveConversations();
      this.renderConversations();
      notify('Conversation archived');
    }
  },
  
  muteConversation(conversationId) {
    const conversation = this.conversations.find(c => c.id === conversationId);
    if (conversation) {
      conversation.settings.muted = true;
      this.saveConversations();
      notify('Conversation muted');
    }
  },
  
  deleteConversation(conversationId) {
    ModalManager.showConfirm({
      title: 'Delete Conversation',
      message: 'Are you sure? This cannot be undone.',
      confirmText: 'Delete',
      cancelText: 'Cancel',
      onConfirm: () => {
        this.conversations = this.conversations.filter(c => c.id !== conversationId);
        this.saveConversations();
        this.renderConversations();
        this.showEmptyChat();
        notify('Conversation deleted');
      }
    });
  },
  
  // Export conversation
  exportConversation(conversationId) {
    const conversation = this.conversations.find(c => c.id === conversationId);
    if (!conversation) return;
    
    const otherParticipant = conversation.participants.find(p => p !== state.user.email);
    const otherUser = state.users.find(u => u.email === otherParticipant) || 
      { name: otherParticipant.split('@')[0] };
    
    let exportText = `TerraTrade Conversation Export\n`;
    exportText += `Conversation with: ${otherUser.name}\n`;
    exportText += `Exported on: ${new Date().toLocaleString()}\n`;
    exportText += `Total messages: ${conversation.messages.length}\n\n`;
    exportText += '='.repeat(50) + '\n\n';
    
    conversation.messages.forEach(msg => {
      const sender = state.users.find(u => u.email === msg.senderId) || 
        { name: msg.senderId.split('@')[0] };
      exportText += `[${new Date(msg.timestamp).toLocaleString()}] ${sender.name}: ${msg.content}\n`;
    });
    
    // Create download
    const blob = new Blob([exportText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `conversation_${otherUser.name}_${Date.now()}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    notify('Conversation exported');
  },
  
  // Reply functionality helpers
  showReplyIndicator(message) {
    const sender = state.users.find(u => u.email === message.senderId) || 
      { name: message.senderId.split('@')[0] };
    
    const chatInput = $('#chatInput');
    if (!chatInput) return;
    
    // Remove existing reply indicator
    this.clearReply();
    
    // Create reply indicator
    const replyIndicator = document.createElement('div');
    replyIndicator.id = 'replyIndicator';
    replyIndicator.className = 'reply-indicator';
    replyIndicator.innerHTML = `
      <div class="reply-header">Replying to ${sender.name}</div>
      <div class="reply-content">${this.escapeHtml(message.content.substring(0, 100))}${message.content.length > 100 ? '...' : ''}</div>
      <button class="reply-close" onclick="MessageSystem.clearReply()">✕</button>
    `;
    
    chatInput.insertBefore(replyIndicator, chatInput.firstChild);
  },
  
  clearReply() {
    this.replyToMessage = null;
    const replyIndicator = $('#replyIndicator');
    if (replyIndicator) {
      replyIndicator.remove();
    }
  },
  
  clearSelection() {
    this.selectedMessages.clear();
    // Remove selection styling from messages
    document.querySelectorAll('.message.selected').forEach(msg => {
      msg.classList.remove('selected');
    });
  },
  
  selectMessage(messageId) {
    const messageEl = document.querySelector(`[data-message-id="${messageId}"]`);
    if (!messageEl) return;
    
    if (this.selectedMessages.has(messageId)) {
      this.selectedMessages.delete(messageId);
      messageEl.classList.remove('selected');
    } else {
      this.selectedMessages.add(messageId);
      messageEl.classList.add('selected');
    }
  },
  
  focusConversationSearch() {
    const searchInput = $('#conversationSearch');
    if (searchInput) {
      searchInput.focus();
      searchInput.select();
    }
  },
  
  // Update message badge in header
  updateMessageBadge() {
    if (!state.user) return;
    
    // Calculate total unread messages
    let totalUnread = 0;
    this.conversations.forEach(conversation => {
      if (conversation.participants.includes(state.user.email)) {
        totalUnread += conversation.unreadCount[state.user.email] || 0;
      }
    });
    
    const badge = $('#messagesBadge');
    if (!badge) return;
    
    if (totalUnread > 0) {
      badge.textContent = totalUnread;
      badge.classList.remove('hidden');
    } else {
      badge.textContent = '0';
      badge.classList.add('hidden');
    }
  }
};

// Update the make offer button handler to use messaging instead
function updateMakeOfferButton() {
  // Remove old handler and add new one
  $('#makeOfferBtn')?.removeEventListener('click', () => {});
  
  // This will be handled by the MessageSystem.setupMessageHandlers
}

/* ===== HELPER FUNCTIONS FOR OFFERS/BIDS/CONTRACTS ===== */

// View offer history
function viewOfferHistory(offerId) {
  const offer = state.offers.find(o => o.id === offerId);
  if (!offer) return;
  
  let historyHtml = `
    <div class="offer-history">
      <h4>Offer #${offer.id} History</h4>
      <div class="history-timeline">
        ${offer.history.map(entry => `
          <div class="history-item">
            <div class="history-date">${new Date(entry.timestamp).toLocaleString()}</div>
            <div class="history-action">${entry.action.toUpperCase()}</div>
            <div class="history-details">${entry.details}</div>
            <div class="history-actor">by ${entry.actor}</div>
          </div>
        `).join('')}
      </div>
    </div>
  `;
  
  ModalManager.showAlert({
    title: 'Offer History',
    message: historyHtml
  });
}

// Withdraw offer
function withdrawOffer(offerId) {
  ModalManager.showConfirm({
    title: 'Withdraw Offer',
    message: 'Are you sure you want to withdraw this offer?',
    confirmText: 'Withdraw',
    cancelText: 'Cancel',
    onConfirm: () => {
      const offer = state.offers.find(o => o.id === offerId);
      if (offer) {
        offer.status = 'withdrawn';
        offer.withdrawnAt = Date.now();
        offer.history.push({
          action: 'withdrawn',
          timestamp: Date.now(),
          actor: state.user.email,
          details: 'Offer withdrawn by buyer'
        });
        saveState();
        notify('Offer withdrawn successfully.');
        
        // Refresh the offers modal if open
        if (ModalManager.isModalOpen('myOffersModal')) {
          openMyOffers();
        }
      }
    }
  });
}

// Export offers
function exportOffers() {
  const myOffers = state.offers.filter(o => o.buyer === state.user.email);
  
  let csvContent = "data:text/csv;charset=utf-8,";
  csvContent += "Offer ID,Property,Price,Status,Earnest Money,Closing Date,Submitted\n";
  
  myOffers.forEach(offer => {
    const listing = state.listings.find(l => l.id === offer.listingId);
    const row = [
      offer.id,
      listing ? `"${listing.title}"` : 'Unknown',
      offer.price,
      offer.status,
      offer.earnestMoney,
      new Date(offer.closingDate).toLocaleDateString(),
      new Date(offer.submittedAt).toLocaleDateString()
    ].join(',');
    csvContent += row + "\n";
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `my_offers_${Date.now()}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  notify('Offers exported successfully!');
}

// Increase bid
function increaseBid(bidId) {
  const newAmount = prompt('Enter new bid amount:');
  if (!newAmount) return;
  
  const amount = Number(newAmount);
  if (isNaN(amount) || amount <= 0) {
    alert('Please enter a valid bid amount.');
    return;
  }
  
  // Find and update bid
  const bidIndex = state.auctionBids.findIndex(b => b.id === bidId);
  if (bidIndex !== -1) {
    state.auctionBids[bidIndex].amount = amount;
    state.auctionBids[bidIndex].timestamp = Date.now();
    saveState();
    notify(`Bid increased to ${fmt(amount)}`);
    
    // Refresh the bids modal if open
    if (ModalManager.isModalOpen('myBidsModal')) {
      openMyBids();
    }
  }
}

// Export bids
function exportBids() {
  const myBids = state.auctionBids.filter(b => b.bidder === state.user.email);
  
  let csvContent = "data:text/csv;charset=utf-8,";
  csvContent += "Bid ID,Property,Amount,Status,Placed Date\n";
  
  myBids.forEach(bid => {
    const listing = state.listings.find(l => l.id === bid.listingId);
    const row = [
      bid.id || Date.now(),
      listing ? `"${listing.title}"` : 'Unknown',
      bid.amount,
      bid.status || 'active',
      new Date(bid.timestamp).toLocaleDateString()
    ].join(',');
    csvContent += row + "\n";
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `my_bids_${Date.now()}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  notify('Bids exported successfully!');
}

// Sign contract
function signContract(contractId) {
  const contract = state.contracts.find(c => c.id === contractId);
  if (!contract) return;
  
  ModalManager.showConfirm({
    title: 'Sign Contract',
    message: `Are you sure you want to electronically sign Contract #${contractId}?`,
    confirmText: 'Sign',
    cancelText: 'Cancel',
    onConfirm: () => {
      signPSAContract(contractId, state.user.email, 'buyer');
      
      // Refresh the contracts modal if open
      if (ModalManager.isModalOpen('myContractsModal')) {
        setTimeout(() => openMyContracts(), 500);
      }
    }
  });
}

// Download contract
function downloadContract(contractId) {
  const contract = state.contracts.find(c => c.id === contractId);
  if (!contract) return;
  
  const listing = state.listings.find(l => l.id === contract.listingId);
  
  let contractText = `PURCHASE AND SALE AGREEMENT\n`;
  contractText += `Contract #${contractId}\n`;
  contractText += `Generated: ${new Date(contract.generatedAt).toLocaleString()}\n\n`;
  contractText += `PROPERTY DETAILS:\n`;
  contractText += `Title: ${listing ? listing.title : 'N/A'}\n`;
  contractText += `Description: ${listing ? listing.desc : 'N/A'}\n`;
  contractText += `Area: ${listing ? listing.area : 'N/A'} sqm\n`;
  contractText += `Location: ${contract.property.location}\n\n`;
  contractText += `FINANCIAL TERMS:\n`;
  contractText += `Purchase Price: ${fmt(contract.purchasePrice)}\n`;
  contractText += `Earnest Money: ${fmt(contract.earnestMoney)}\n`;
  contractText += `Closing Date: ${new Date(contract.closingDate).toLocaleDateString()}\n\n`;
  contractText += `PARTIES:\n`;
  contractText += `Buyer: ${contract.buyer}\n`;
  contractText += `Seller: ${contract.seller}\n\n`;
  contractText += `SIGNATURES (${contract.signaturesCollected}/${contract.signaturesRequired}):\n`;
  contract.signatures.forEach(sig => {
    contractText += `${sig.role}: ${sig.signer} - Signed: ${new Date(sig.signedAt).toLocaleString()}\n`;
  });
  
  if (contract.inclusions) {
    contractText += `\nINCLUSIONS:\n${contract.inclusions}\n`;
  }
  
  if (contract.exclusions) {
    contractText += `\nEXCLUSIONS:\n${contract.exclusions}\n`;
  }
  
  if (contract.specialTerms) {
    contractText += `\nSPECIAL TERMS:\n${contract.specialTerms}\n`;
  }
  
  const blob = new Blob([contractText], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `PSA_Contract_${contractId}_${Date.now()}.txt`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  
  notify('Contract downloaded successfully!');
}

// Export contracts
function exportContracts() {
  const myContracts = state.contracts.filter(c => c.buyer === state.user.email);
  
  let csvContent = "data:text/csv;charset=utf-8,";
  csvContent += "Contract ID,Property,Purchase Price,Status,Closing Date,Generated Date\n";
  
  myContracts.forEach(contract => {
    const listing = state.listings.find(l => l.id === contract.listingId);
    const row = [
      contract.id,
      listing ? `"${listing.title}"` : 'Unknown',
      contract.purchasePrice,
      contract.status,
      new Date(contract.closingDate).toLocaleDateString(),
      new Date(contract.generatedAt).toLocaleDateString()
    ].join(',');
    csvContent += row + "\n";
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `my_contracts_${Date.now()}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  notify('Contracts exported successfully!');
}

// Accept received offer (seller/broker)
function acceptReceivedOffer(offerId) {
  ModalManager.showConfirm({
    title: 'Accept Offer',
    message: 'Are you sure you want to accept this offer?',
    confirmText: 'Accept',
    cancelText: 'Cancel',
    onConfirm: () => {
      acceptOffer(offerId);
      
      // Refresh the received offers modal if open
      if (ModalManager.isModalOpen('receivedOffersModal')) {
        setTimeout(() => openReceivedOffers(), 500);
      }
    }
  });
}

// Counter received offer (seller/broker)
function counterReceivedOffer(offerId) {
  const counterPrice = prompt('Enter counter-offer price:');
  if (!counterPrice) return;
  
  const price = Number(counterPrice);
  if (isNaN(price) || price <= 0) {
    alert('Please enter a valid price.');
    return;
  }
  
  const comments = prompt('Add comments (optional):') || 'Counter-offer submitted';
  
  createCounterOffer(offerId, {
    price: price,
    earnestMoney: Math.round(price * 0.05),
    buyerComments: comments,
    modifiedBy: state.user.email
  });
  
  // Refresh the received offers modal if open
  if (ModalManager.isModalOpen('receivedOffersModal')) {
    setTimeout(() => openReceivedOffers(), 500);
  }
}

// Reject received offer (seller/broker)
function rejectReceivedOffer(offerId) {
  const reason = prompt('Reason for rejection (optional):') || 'Offer declined';
  
  ModalManager.showConfirm({
    title: 'Reject Offer',
    message: 'Are you sure you want to reject this offer?',
    confirmText: 'Reject',
    cancelText: 'Cancel',
    onConfirm: () => {
      rejectOffer(offerId, reason);
      
      // Refresh the received offers modal if open
      if (ModalManager.isModalOpen('receivedOffersModal')) {
        setTimeout(() => openReceivedOffers(), 500);
      }
    }
  });
}

// Contact buyer (seller/broker)
function contactBuyer(buyerEmail) {
  // Find or create conversation with buyer
  let conversation = MessageSystem.conversations.find(c => 
    c.participants.includes(state.user.email) && 
    c.participants.includes(buyerEmail)
  );
  
  if (!conversation) {
    conversation = MessageSystem.createConversation(buyerEmail, {
      initialMessage: `Hi! I'd like to discuss your offer/bid.`
    });
  }
  
  // Close current modal and open messaging
  ModalManager.closeAllModals();
  MessageSystem.openMessagingModal(conversation.id);
}

// View bid history
function viewBidHistory(listingId) {
  const listing = state.listings.find(l => l.id === listingId);
  if (!listing) return;
  
  const allBids = [
    ...(listing.bids || []),
    ...state.auctionBids.filter(b => b.listingId === listingId)
  ].sort((a, b) => b.timestamp - a.timestamp);
  
  let historyHtml = `
    <div class="bid-history">
      <h4>All Bids for ${listing.title}</h4>
      <div class="bids-list">
        ${allBids.length === 0 ? '<p>No bids placed yet.</p>' : 
          allBids.map(bid => `
            <div class="bid-history-item">
              <div class="bid-amount">${fmt(bid.amount)}</div>
              <div class="bid-bidder">${bid.bidderName || bid.bidder}</div>
              <div class="bid-date">${new Date(bid.timestamp).toLocaleString()}</div>
            </div>
          `).join('')
        }
      </div>
    </div>
  `;
  
  ModalManager.showAlert({
    title: 'Bid History',
    message: historyHtml
  });
}

// Export received offers
function exportReceivedOffers() {
  const myListings = state.listings.filter(l => l.owner === state.user.email);
  const receivedOffers = [];
  
  myListings.forEach(listing => {
    if (listing.offers && listing.offers.length > 0) {
      listing.offers.forEach(offer => {
        receivedOffers.push({
          ...offer,
          listingTitle: listing.title
        });
      });
    }
  });
  
  let csvContent = "data:text/csv;charset=utf-8,";
  csvContent += "Offer ID,Property,Buyer,Price,Status,Earnest Money,Received Date\n";
  
  receivedOffers.forEach(offer => {
    const row = [
      offer.id,
      `"${offer.listingTitle}"`,
      offer.buyerName || offer.buyer,
      offer.price,
      offer.status,
      offer.earnestMoney,
      new Date(offer.submittedAt).toLocaleDateString()
    ].join(',');
    csvContent += row + "\n";
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `received_offers_${Date.now()}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  notify('Received offers exported successfully!');
}

// Export received bids
function exportReceivedBids() {
  const myAuctionListings = state.listings.filter(l => l.owner === state.user.email && l.type === 'auction');
  const receivedBids = [];
  
  myAuctionListings.forEach(listing => {
    if (listing.bids && listing.bids.length > 0) {
      listing.bids.forEach(bid => {
        receivedBids.push({
          ...bid,
          listingTitle: listing.title
        });
      });
    }
  });
  
  // Also check global auction bids
  state.auctionBids.forEach(bid => {
    const listing = state.listings.find(l => l.id === bid.listingId && l.owner === state.user.email);
    if (listing) {
      receivedBids.push({
        ...bid,
        listingTitle: listing.title
      });
    }
  });
  
  let csvContent = "data:text/csv;charset=utf-8,";
  csvContent += "Property,Bidder,Amount,Placed Date\n";
  
  receivedBids.forEach(bid => {
    const row = [
      `"${bid.listingTitle}"`,
      bid.bidderName || bid.bidder,
      bid.amount,
      new Date(bid.timestamp).toLocaleDateString()
    ].join(',');
    csvContent += row + "\n";
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `received_bids_${Date.now()}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  notify('Received bids exported successfully!');
}

/* ===== QUICK OFFER/BID INLINE FUNCTIONS ===== */

// Setup quick offer section in listing modal
function setupQuickOfferSection(listing) {
  const quickOfferSection = $('#quickOfferSection');
  if (!quickOfferSection) return;
  
  quickOfferSection.innerHTML = `
    <div class="quick-action-header">
      <h4>💰 Make an Offer</h4>
      <p class="muted">Submit your offer directly</p>
    </div>
    
    <div class="quick-offer-form">
      <div class="input-row">
        <label>Offer Price (₱)</label>
        <input type="number" id="quickOfferPrice" value="${listing.price}" min="1" step="1000">
      </div>
      
      <div class="input-row">
        <label>Earnest Money (₱)</label>
        <input type="number" id="quickOfferEarnest" value="${Math.round(listing.price * 0.05)}" min="1" step="1000">
      </div>
      
      <div class="input-row">
        <label>Closing Date</label>
        <input type="date" id="quickOfferClosing" value="${new Date(Date.now() + 45 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]}">
      </div>
      
      <div class="input-row">
        <label>Comments (Optional)</label>
        <textarea id="quickOfferComments" placeholder="Any special terms or comments..."></textarea>
      </div>
      
      <div class="quick-action-buttons">
        <button class="btn primary" onclick="submitQuickOffer()">
          📄 Submit Offer
        </button>
        <button class="btn ghost" onclick="openEnhancedOfferModal()">
          ⚙️ Advanced Options
        </button>
      </div>
    </div>
    
    <div class="existing-offers">
      ${listing.offers && listing.offers.length > 0 ? `
        <div class="offers-summary">
          <strong>Existing Offers (${listing.offers.length}):</strong>
          <div class="offers-list">
            ${listing.offers.slice(0, 3).map(offer => `
              <div class="offer-item">
                <div class="offer-amount">${fmt(offer.price)}</div>
                <div class="offer-status ${offer.status}">${offer.status.toUpperCase()}</div>
                <div class="offer-date">${new Date(offer.submittedAt).toLocaleDateString()}</div>
              </div>
            `).join('')}
            ${listing.offers.length > 3 ? `<div class="more-offers">+${listing.offers.length - 3} more</div>` : ''}
          </div>
        </div>
      ` : '<div class="no-offers">No offers yet - be the first!</div>'}
    </div>
  `;
}

// Setup quick bid section in listing modal
function setupQuickBidSection(listing) {
  const quickBidSection = $('#quickBidSection');
  if (!quickBidSection) return;
  
  // Get current high bid
  const allBids = [...(listing.bids || []), ...state.auctionBids.filter(b => b.listingId === listing.id)];
  const highestBid = allBids.length > 0 ? Math.max(...allBids.map(b => b.amount)) : listing.price;
  const minimumBid = highestBid + (listing.bidIncrement || 10000);
  
  // Calculate time remaining
  const timeRemaining = listing.auctionEnds - Date.now();
  const isEnded = timeRemaining <= 0;
  
  quickBidSection.innerHTML = `
    <div class="quick-action-header">
      <h4>🏗️ Place a Bid</h4>
      <p class="muted">${isEnded ? 'Auction has ended' : 'Submit your bid for this auction'}</p>
    </div>
    
    <div class="auction-status">
      <div class="auction-info-grid">
        <div class="auction-stat">
          <div class="stat-label">Current High Bid</div>
          <div class="stat-value">${fmt(highestBid)}</div>
        </div>
        <div class="auction-stat">
          <div class="stat-label">Total Bids</div>
          <div class="stat-value">${allBids.length}</div>
        </div>
        <div class="auction-stat">
          <div class="stat-label">Time Left</div>
          <div class="stat-value" id="quickAuctionTimer">${isEnded ? 'ENDED' : formatTimeRemaining(timeRemaining)}</div>
        </div>
        <div class="auction-stat">
          <div class="stat-label">Reserve Price</div>
          <div class="stat-value">${listing.reservePrice ? fmt(listing.reservePrice) : 'None'}</div>
        </div>
      </div>
    </div>
    
    ${!isEnded ? `
      <div class="quick-bid-form">
        <div class="input-row">
          <label>Your Bid (₱) - Minimum: ${fmt(minimumBid)}</label>
          <input type="number" id="quickBidAmount" value="${minimumBid}" min="${minimumBid}" step="1000">
        </div>
        
        <div class="input-row">
          <label>Comments (Optional)</label>
          <textarea id="quickBidComments" placeholder="Any comments about your bid..."></textarea>
        </div>
        
        <div class="quick-action-buttons">
          <button class="btn primary" onclick="submitQuickBid()">
            🏗️ Place Bid
          </button>
          <button class="btn ghost" onclick="openAuction(${listing.id})">
            🔍 Full Auction View
          </button>
        </div>
      </div>
    ` : `
      <div class="auction-ended">
        <p><strong>This auction has ended.</strong></p>
        ${allBids.length > 0 ? `<p>Winning bid: ${fmt(highestBid)}</p>` : '<p>No bids were placed.</p>'}
      </div>
    `}
    
    <div class="existing-bids">
      ${allBids.length > 0 ? `
        <div class="bids-summary">
          <strong>Recent Bids (${allBids.length}):</strong>
          <div class="bids-list">
            ${allBids.slice(-5).reverse().map(bid => `
              <div class="bid-item">
                <div class="bid-amount">${fmt(bid.amount)}</div>
                <div class="bid-bidder">${bid.bidderName || bid.bidder}</div>
                <div class="bid-date">${new Date(bid.timestamp).toLocaleDateString()}</div>
              </div>
            `).join('')}
          </div>
        </div>
      ` : '<div class="no-bids">No bids yet - be the first!</div>'}
    </div>
  `;
  
  // Start timer if auction is still active
  if (!isEnded) {
    startQuickAuctionTimer(listing.auctionEnds);
  }
}

// Display offers/bids summary in listing modal
function displayOffersBidsSummary(listing) {
  const summaryBox = $('#offersBidsSummary');
  if (!summaryBox) return;
  
  let summaryHtml = '';
  
  if (listing.type === 'auction') {
    // Show bids summary
    const allBids = [...(listing.bids || []), ...state.auctionBids.filter(b => b.listingId === listing.id)];
    const highestBid = allBids.length > 0 ? Math.max(...allBids.map(b => b.amount)) : 0;
    
    summaryHtml = `
      <div class="activity-summary">
        <h4>🏗️ Auction Activity</h4>
        <div class="activity-stats">
          <div class="stat-item">
            <span class="stat-value">${allBids.length}</span>
            <span class="stat-label">Total Bids</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">${highestBid > 0 ? fmt(highestBid) : 'None'}</span>
            <span class="stat-label">Highest Bid</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">${new Set(allBids.map(b => b.bidder)).size}</span>
            <span class="stat-label">Bidders</span>
          </div>
        </div>
      </div>
    `;
  } else {
    // Show offers summary
    const offers = listing.offers || [];
    const highestOffer = offers.length > 0 ? Math.max(...offers.map(o => o.price)) : 0;
    const pendingOffers = offers.filter(o => o.status === 'submitted').length;
    
    summaryHtml = `
      <div class="activity-summary">
        <h4>💰 Offers Activity</h4>
        <div class="activity-stats">
          <div class="stat-item">
            <span class="stat-value">${offers.length}</span>
            <span class="stat-label">Total Offers</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">${pendingOffers}</span>
            <span class="stat-label">Pending</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">${highestOffer > 0 ? fmt(highestOffer) : 'None'}</span>
            <span class="stat-label">Highest Offer</span>
          </div>
        </div>
      </div>
    `;
  }
  
  summaryBox.innerHTML = summaryHtml;
}

// Submit quick offer
function submitQuickOffer() {
  if (!state.currentListing || !state.user) {
    ModalManager.showAlert({
      title: 'Error',
      message: 'Please login to submit an offer.'
    });
    return;
  }
  
  requireKYC(() => {
    const price = Number($('#quickOfferPrice').value || 0);
    const earnestMoney = Number($('#quickOfferEarnest').value || 0);
    const closingDate = $('#quickOfferClosing').value;
    const comments = $('#quickOfferComments').value.trim();
    
    // Validation
    if (!price || price <= 0) {
      alert('Please enter a valid offer price.');
      return;
    }
    
    if (!earnestMoney || earnestMoney <= 0) {
      alert('Please enter a valid earnest money amount.');
      return;
    }
    
    if (!closingDate) {
      alert('Please select a closing date.');
      return;
    }
    
    // Create quick offer
    const offer = {
      id: state.offerIdCounter++,
      listingId: state.currentListing.id,
      version: 1,
      buyer: state.user.email,
      buyerName: state.user.name,
      status: 'submitted',
      submittedAt: Date.now(),
      price: price,
      earnestMoney: earnestMoney,
      closingDate: closingDate,
      buyerComments: comments,
      contingencies: {
        financing: { enabled: false },
        survey: { enabled: false },
        title: { enabled: false },
        environmental: { enabled: false }
      },
      history: [{
        action: 'submitted',
        timestamp: Date.now(),
        actor: state.user.email,
        details: `Quick offer submitted for ${fmt(price)}`
      }]
    };
    
    // Add to listing's offers array
    state.currentListing.offers.push(offer);
    
    // Add to global offers array
    state.offers.push(offer);
    
    // Set as current offer
    state.currentOffer = offer;
    
    notify(`Quick offer of ${fmt(price)} submitted for ${state.currentListing.title}`);
    
    // Clear form
    $('#quickOfferPrice').value = state.currentListing.price;
    $('#quickOfferEarnest').value = Math.round(state.currentListing.price * 0.05);
    $('#quickOfferComments').value = '';
    
    // Refresh the offers summary
    displayOffersBidsSummary(state.currentListing);
    setupQuickOfferSection(state.currentListing);
    
    // Simulate seller response
    simulateSellerResponse(offer);
    
    saveState();
  });
}

// Submit quick bid
function submitQuickBid() {
  if (!state.currentListing || !state.user) {
    ModalManager.showAlert({
      title: 'Error',
      message: 'Please login to place a bid.'
    });
    return;
  }
  
  requireKYC(() => {
    const amount = Number($('#quickBidAmount').value || 0);
    const comments = $('#quickBidComments').value.trim();
    
    // Get current high bid
    const allBids = [...(state.currentListing.bids || []), ...state.auctionBids.filter(b => b.listingId === state.currentListing.id)];
    const highestBid = allBids.length > 0 ? Math.max(...allBids.map(b => b.amount)) : state.currentListing.price;
    const minimumBid = highestBid + (state.currentListing.bidIncrement || 10000);
    
    // Validation
    if (!amount || amount < minimumBid) {
      alert(`Bid must be at least ${fmt(minimumBid)}`);
      return;
    }
    
    // Check if auction is still active
    if (state.currentListing.auctionEnds <= Date.now()) {
      alert('This auction has ended.');
      return;
    }
    
    // Create quick bid
    const bid = {
      id: Date.now() + Math.random(),
      listingId: state.currentListing.id,
      bidder: state.user.email,
      bidderName: state.user.name,
      amount: amount,
      timestamp: Date.now(),
      status: 'active',
      comments: comments
    };
    
    // Add to global auction bids
    state.auctionBids.push(bid);
    
    // Add to listing's bids array
    if (!state.currentListing.bids) state.currentListing.bids = [];
    state.currentListing.bids.push(bid);
    
    notify(`Bid of ${fmt(amount)} placed on ${state.currentListing.title}`);
    
    // Clear form
    const newMinimum = amount + (state.currentListing.bidIncrement || 10000);
    $('#quickBidAmount').value = newMinimum;
    $('#quickBidComments').value = '';
    
    // Refresh the bids summary
    displayOffersBidsSummary(state.currentListing);
    setupQuickBidSection(state.currentListing);
    
    saveState();
  });
}

// Format time remaining for auction
function formatTimeRemaining(milliseconds) {
  if (milliseconds <= 0) return 'ENDED';
  
  const seconds = Math.floor(milliseconds / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);
  
  if (days > 0) return `${days}d ${hours % 24}h`;
  if (hours > 0) return `${hours}h ${minutes % 60}m`;
  if (minutes > 0) return `${minutes}m ${seconds % 60}s`;
  return `${seconds}s`;
}

// Start quick auction timer
function startQuickAuctionTimer(auctionEnds) {
  const timer = $('#quickAuctionTimer');
  if (!timer) return;
  
  const updateTimer = () => {
    const remaining = auctionEnds - Date.now();
    if (remaining <= 0) {
      timer.textContent = 'ENDED';
      timer.parentElement.classList.add('ended');
      
      // Refresh the bid section to show ended state
      if (state.currentListing) {
        setupQuickBidSection(state.currentListing);
      }
      
      return;
    }
    
    timer.textContent = formatTimeRemaining(remaining);
    
    // Add urgency styling when less than 1 hour remaining
    if (remaining < 3600000) {
      timer.parentElement.classList.add('urgent');
    }
  };
  
  updateTimer();
  const interval = setInterval(updateTimer, 1000);
  
  // Clear interval when listing modal is closed
  const originalCloseModal = ModalManager.closeModal;
  ModalManager.closeModal = function(modalId, silent = false) {
    if (modalId === 'listingModal') {
      clearInterval(interval);
    }
    return originalCloseModal.call(this, modalId, silent);
  };
}

/* ===== IDENTIFICATION MODAL FUNCTIONS ===== */

// Open identification modal for offers or bids
function openIdentificationModal(type) {
  if (!state.user) {
    ModalManager.showAlert({
      title: 'Login Required',
      message: 'Please login to make an offer or bid.'
    });
    return;
  }

  if (!state.currentListing) {
    ModalManager.showAlert({
      title: 'Error', 
      message: 'No listing selected.'
    });
    return;
  }

  // Set the modal type (bid or offer)
  const modal = $('#offerIdentificationModal');
  if (modal) {
    modal.dataset.type = type;
    
    // Update modal title based on type
    const titleElement = modal.querySelector('h3');
    if (titleElement) {
      titleElement.textContent = type === 'bid' ? '🏗️ Place Bid - Identity Verification' : '💰 Make Offer - Identity Verification';
    }

    // Update the offer amount field based on listing type and current bids/offers
    let suggestedAmount = state.currentListing.price;
    if (type === 'bid' && state.currentListing.type === 'auction') {
      const allBids = [...(state.currentListing.bids || []), ...state.auctionBids.filter(b => b.listingId === state.currentListing.id)];
      const highestBid = allBids.length > 0 ? Math.max(...allBids.map(b => b.amount)) : state.currentListing.price;
      suggestedAmount = highestBid + (state.currentListing.bidIncrement || 10000);
    }
    
    const amountInput = modal.querySelector('#verifyOfferAmount');
    if (amountInput) {
      amountInput.value = suggestedAmount;
    }

    // Clear form data
    clearIdentificationForm();
    
    ModalManager.openModal('offerIdentificationModal');
  }
}

// Setup identification modal handlers
function setupIdentificationModalHandlers() {
  const modal = $('#offerIdentificationModal');
  if (!modal) return;
  
  // Handle purpose selection
  const purposeSelect = modal.querySelector('#offerPurpose');
  if (purposeSelect) {
    purposeSelect.addEventListener('change', (e) => {
      const otherField = modal.querySelector('#otherPurposeField');
      if (otherField) {
        if (e.target.value === 'other') {
          otherField.classList.remove('hidden');
        } else {
          otherField.classList.add('hidden');
        }
      }
    });
  }
  
  // Handle ID type selection
  const idTypeSelect = modal.querySelector('#offerIdType');
  if (idTypeSelect) {
    idTypeSelect.addEventListener('change', (e) => {
      const otherField = modal.querySelector('#otherIdField');
      if (otherField) {
        if (e.target.value === 'other') {
          otherField.classList.remove('hidden');
        } else {
          otherField.classList.add('hidden');
        }
      }
    });
  }
  
  // Setup submit button
  const submitBtn = modal.querySelector('#verifyAndProceedBtn');
  if (submitBtn) {
    submitBtn.addEventListener('click', submitIdentificationForm);
  }
}

// Clear identification form data
function clearIdentificationForm() {
  const modal = $('#offerIdentificationModal');
  if (!modal) return;
  
  // Clear all form inputs except the amount which is set in openIdentificationModal
  const inputs = modal.querySelectorAll('input[type="text"], input[type="tel"], input[type="email"], textarea, select');
  inputs.forEach(input => {
    if (input.id !== 'verifyOfferAmount') {
      input.value = '';
    }
  });
  
  // Clear file inputs
  const fileInputs = modal.querySelectorAll('input[type="file"]');
  fileInputs.forEach(input => {
    input.value = '';
  });
  
  // Uncheck checkboxes
  const checkboxes = modal.querySelectorAll('input[type="checkbox"]');
  checkboxes.forEach(checkbox => {
    checkbox.checked = false;
  });
}

// Submit identification form and proceed with bid/offer
function submitIdentificationForm() {
  const modal = $('#offerIdentificationModal');
  if (!modal) return;
  
  const type = modal.dataset.type;
  
  // Validate all required fields
  if (!validateIdentificationForm()) {
    return;
  }
  
  // Get form data
  const formData = collectIdentificationFormData();
  
  // Close the identification modal
  ModalManager.closeModal('offerIdentificationModal');
  
  // Show loading
  ModalManager.showLoading('Processing your ' + type + '...');
  
  // Simulate verification process
  setTimeout(() => {
    ModalManager.hideLoading();
    
    // Proceed with the appropriate action
    if (type === 'bid') {
      processBidWithIdentification(formData);
    } else {
      processOfferWithIdentification(formData);
    }
  }, 2000 + Math.random() * 2000); // 2-4 seconds
}

// Validate identification form
function validateIdentificationForm() {
  const modal = $('#offerIdentificationModal');
  if (!modal) return false;
  
  // Validate amount is positive
  const amount = Number(modal.querySelector('#verifyOfferAmount')?.value || 0);
  if (amount <= 0) {
    alert('Please enter a valid offer amount.');
    modal.querySelector('#verifyOfferAmount')?.focus();
    return false;
  }
  
  // Validate required fields
  const requiredFields = [
    { id: 'verifyOfferAmount', name: 'Offer Amount' },
    { id: 'offerPurpose', name: 'Purpose of Offer' },
    { id: 'offerLegalName', name: 'Full Legal Name' },
    { id: 'offerIdNumber', name: 'ID Number' },
    { id: 'offerIdType', name: 'ID Type' },
    { id: 'offerPhone', name: 'Contact Phone' }
  ];
  
  for (let field of requiredFields) {
    const element = modal.querySelector('#' + field.id);
    if (!element || !element.value.trim()) {
      alert(`Please fill in the ${field.name} field.`);
      element?.focus();
      return false;
    }
  }
  
  // Validate email format
  const email = modal.querySelector('#offerAltEmail')?.value;
  if (email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      alert('Please enter a valid email address.');
      modal.querySelector('#offerAltEmail')?.focus();
      return false;
    }
  }
  
  // Check file uploads
  const frontId = modal.querySelector('#offerIdFront')?.files?.[0];
  const backId = modal.querySelector('#offerIdBack')?.files?.[0];
  
  if (!frontId) {
    alert('Please upload the front side of your ID.');
    modal.querySelector('#offerIdFront')?.focus();
    return false;
  }
  
  if (!backId) {
    alert('Please upload the back side of your ID.');
    modal.querySelector('#offerIdBack')?.focus();
    return false;
  }
  
  // Check confirmation checkboxes
  const confirmations = modal.querySelectorAll('input[type="checkbox"][required]');
  for (let checkbox of confirmations) {
    if (!checkbox.checked) {
      alert('Please check all required confirmation boxes.');
      checkbox.focus();
      return false;
    }
  }
  
  return true;
}

// Collect identification form data
function collectIdentificationFormData() {
  const modal = $('#offerIdentificationModal');
  if (!modal) return null;
  
  // Handle purpose field
  const purposeSelect = modal.querySelector('#offerPurpose');
  let purpose = '';
  if (purposeSelect) {
    purpose = purposeSelect.value;
    if (purpose === 'other') {
      const otherPurposeField = modal.querySelector('#otherPurposeText');
      if (otherPurposeField) {
        purpose = otherPurposeField.value.trim();
      }
    }
  }
  
  // Handle ID type field
  const idTypeSelect = modal.querySelector('#offerIdType');
  let idType = '';
  if (idTypeSelect) {
    idType = idTypeSelect.value;
    if (idType === 'other') {
      const otherIdField = modal.querySelector('#otherIdTypeText');
      if (otherIdField) {
        idType = otherIdField.value.trim();
      }
    }
  }
  
  return {
    amount: Number(modal.querySelector('#verifyOfferAmount')?.value || 0),
    purpose: purpose,
    fullLegalName: modal.querySelector('#offerLegalName')?.value?.trim() || '',
    idNumber: modal.querySelector('#offerIdNumber')?.value?.trim() || '',
    idType: idType,
    contactEmail: modal.querySelector('#offerAltEmail')?.value?.trim() || '',
    contactPhone: modal.querySelector('#offerPhone')?.value?.trim() || '',
    idFrontFile: modal.querySelector('#offerIdFront')?.files?.[0],
    idBackFile: modal.querySelector('#offerIdBack')?.files?.[0],
    confirmIdentity: modal.querySelector('#offerTermsCheck')?.checked || false,
    confirmLegality: modal.querySelector('#offerConsentCheck')?.checked || false,
    confirmProcessing: true // Default to true as this checkbox doesn't exist in HTML
  };
}

// Process bid with identification data
function processBidWithIdentification(formData) {
  if (!state.currentListing || state.currentListing.type !== 'auction') {
    ModalManager.showAlert({
      title: 'Error',
      message: 'This is not a valid auction listing.'
    });
    return;
  }
  
  // Check if auction is still active
  if (state.currentListing.auctionEnds <= Date.now()) {
    ModalManager.showAlert({
      title: 'Auction Ended',
      message: 'This auction has already ended.'
    });
    return;
  }
  
  // Validate bid amount
  const allBids = [...(state.currentListing.bids || []), ...state.auctionBids.filter(b => b.listingId === state.currentListing.id)];
  const highestBid = allBids.length > 0 ? Math.max(...allBids.map(b => b.amount)) : state.currentListing.price;
  const minimumBid = highestBid + (state.currentListing.bidIncrement || 10000);
  
  if (formData.amount < minimumBid) {
    ModalManager.showAlert({
      title: 'Invalid Bid',
      message: `Bid must be at least ${fmt(minimumBid)}.`
    });
    return;
  }
  
  // Create verified bid with identification data
  const bid = {
    id: Date.now() + Math.random(),
    listingId: state.currentListing.id,
    bidder: state.user.email,
    bidderName: state.user.name,
    amount: formData.amount,
    timestamp: Date.now(),
    status: 'active',
    verified: true,
    identityVerification: {
      fullLegalName: formData.fullLegalName,
      idNumber: formData.idNumber,
      idType: formData.idType,
      contactEmail: formData.contactEmail,
      contactPhone: formData.contactPhone,
      verifiedAt: Date.now(),
      purpose: formData.purpose
    }
  };
  
  // Add to global auction bids
  state.auctionBids.push(bid);
  
  // Add to listing's bids array
  if (!state.currentListing.bids) state.currentListing.bids = [];
  state.currentListing.bids.push(bid);
  
  // Close listing modal and save state
  ModalManager.closeModal('listingModal');
  saveState();
  
  // Show success message
  ModalManager.showAlert({
    title: 'Bid Placed Successfully',
    message: `Your verified bid of ${fmt(formData.amount)} has been placed on ${state.currentListing.title}. Your identity has been verified for this transaction.`
  });
  
  notify(`Verified bid of ${fmt(formData.amount)} placed on ${state.currentListing.title}`);
}

// Process offer with identification data  
function processOfferWithIdentification(formData) {
  if (!state.currentListing) {
    ModalManager.showAlert({
      title: 'Error',
      message: 'No listing selected.'
    });
    return;
  }
  
  // Create verified offer with identification data
  const offer = {
    id: state.offerIdCounter++,
    listingId: state.currentListing.id,
    version: 1,
    buyer: state.user.email,
    buyerName: state.user.name,
    status: 'submitted',
    submittedAt: Date.now(),
    price: formData.amount,
    earnestMoney: Math.round(formData.amount * 0.05), // Default 5%
    closingDate: new Date(Date.now() + 45 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 45 days
    buyerComments: formData.purpose,
    verified: true,
    identityVerification: {
      fullLegalName: formData.fullLegalName,
      idNumber: formData.idNumber,
      idType: formData.idType,
      contactEmail: formData.contactEmail,
      contactPhone: formData.contactPhone,
      verifiedAt: Date.now(),
      purpose: formData.purpose
    },
    contingencies: {
      financing: { enabled: false },
      survey: { enabled: false },
      title: { enabled: false },
      environmental: { enabled: false }
    },
    history: [{
      action: 'submitted',
      timestamp: Date.now(),
      actor: state.user.email,
      details: `Verified offer submitted for ${fmt(formData.amount)}`
    }]
  };
  
  // Add to listing's offers array
  if (!state.currentListing.offers) state.currentListing.offers = [];
  state.currentListing.offers.push(offer);
  
  // Add to global offers array
  state.offers.push(offer);
  
  // Set as current offer
  state.currentOffer = offer;
  
  // Close listing modal and save state
  ModalManager.closeModal('listingModal');
  saveState();
  
  // Show success message
  ModalManager.showAlert({
    title: 'Offer Submitted Successfully',
    message: `Your verified offer of ${fmt(formData.amount)} has been submitted for ${state.currentListing.title}. Your identity has been verified for this transaction.`
  });
  
  notify(`Verified offer of ${fmt(formData.amount)} submitted for ${state.currentListing.title}`);
  
  // Simulate seller response
  simulateSellerResponse(offer);
}

// Simple streamlined make offer/bid function 
function makeBidOffer() {
  if (!state.currentListing || !state.user) return;
  
  const isAuction = state.currentListing.type === "auction";
  const actionType = isAuction ? "bid" : "offer";
  const actionTitle = isAuction ? "Place Bid" : "Make Offer";
  
  // Get suggested amount
  let suggestedAmount = state.currentListing.price;
  if (isAuction) {
    const allBids = [...(state.currentListing.bids || []), ...state.auctionBids.filter(b => b.listingId === state.currentListing.id)];
    const highestBid = allBids.length > 0 ? Math.max(...allBids.map(b => b.amount)) : state.currentListing.price;
    suggestedAmount = highestBid + (state.currentListing.bidIncrement || 10000);
  }
  
  // Prompt user for amount
  const amountStr = prompt(`${actionTitle} - Enter your ${actionType} amount (₱):`, suggestedAmount);
  if (!amountStr) return;
  
  const amount = Number(amountStr);
  if (isNaN(amount) || amount <= 0) {
    alert('Please enter a valid amount.');
    return;
  }
  
  // For auctions, check minimum bid
  if (isAuction) {
    const allBids = [...(state.currentListing.bids || []), ...state.auctionBids.filter(b => b.listingId === state.currentListing.id)];
    const highestBid = allBids.length > 0 ? Math.max(...allBids.map(b => b.amount)) : state.currentListing.price;
    const minimumBid = highestBid + (state.currentListing.bidIncrement || 10000);
    
    if (amount < minimumBid) {
      alert(`Bid must be at least ${fmt(minimumBid)}`);
      return;
    }
    
    if (state.currentListing.auctionEnds <= Date.now()) {
      alert('This auction has ended.');
      return;
    }
  }
  
  if (isAuction) {
    // Create and store bid
    const bid = {
      id: Date.now() + Math.random(),
      listingId: state.currentListing.id,
      bidder: state.user.email,
      bidderName: state.user.name,
      amount: amount,
      timestamp: Date.now(),
      status: 'active',
      comments: ''
    };
    
    // Add to global auction bids
    state.auctionBids.push(bid);
    
    // Add to listing's bids array
    if (!state.currentListing.bids) state.currentListing.bids = [];
    state.currentListing.bids.push(bid);
    
    notify(`Bid of ${fmt(amount)} placed on ${state.currentListing.title}`);
  } else {
    // Create and store offer
    const offer = {
      id: state.offerIdCounter++,
      listingId: state.currentListing.id,
      version: 1,
      buyer: state.user.email,
      buyerName: state.user.name,
      status: 'submitted',
      submittedAt: Date.now(),
      price: amount,
      earnestMoney: Math.round(amount * 0.05), // Default 5%
      closingDate: new Date(Date.now() + 45 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 45 days
      buyerComments: '',
      contingencies: {
        financing: { enabled: false },
        survey: { enabled: false },
        title: { enabled: false },
        environmental: { enabled: false }
      },
      history: [{
        action: 'submitted',
        timestamp: Date.now(),
        actor: state.user.email,
        details: `Offer submitted for ${fmt(amount)}`
      }]
    };
    
    // Add to listing's offers array
    if (!state.currentListing.offers) state.currentListing.offers = [];
    state.currentListing.offers.push(offer);
    
    // Add to global offers array
    state.offers.push(offer);
    
    // Set as current offer
    state.currentOffer = offer;
    
    notify(`Offer of ${fmt(amount)} submitted for ${state.currentListing.title}`);
    
    // Simulate seller response
    simulateSellerResponse(offer);
  }
  
  // Close listing modal and save state
  ModalManager.closeModal('listingModal');
  saveState();
}

/* final hookup on DOM ready */
document.addEventListener("DOMContentLoaded", ()=>{
  console.log('DOM Content Loaded');
  renderListings();
  setupHandlers();
  renderNotifications();
  updateAuthArea();
  renderSavedSearches();
  saveState();
  
  // Initialize messaging system
  console.log('About to initialize MessageSystem');
  MessageSystem.init();
  console.log('MessageSystem initialized');
  
  // Setup identification modal handlers
  setupIdentificationModalHandlers();
  
  // Setup messaging modal close button
  $('#messagingClose')?.addEventListener('click', () => ModalManager.closeModal('messagingModal'));
  
  // Setup new conversation modal close button
  $('#newConversationClose')?.addEventListener('click', () => ModalManager.closeModal('newConversationModal'));
  
  // Setup message button handler (moved here to ensure the button exists)
  $("#messagesBtn")?.addEventListener("click", ()=> MessageSystem.openMessagingModal());
});

