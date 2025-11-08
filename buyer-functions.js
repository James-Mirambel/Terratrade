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

// Create My Offers Modal
function createMyOffersModal(offers) {
  let modal = $('#myOffersModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'myOffersModal';
    modal.className = 'modal hidden';
    document.body.appendChild(modal);
  }
  
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 800px;">
      <button class="modal-close">✕</button>
      <h3>📋 My Offers (${offers.length})</h3>
      
      <div class="offers-list">
        ${offers.map(offer => {
          const listing = state.listings.find(l => l.id === offer.listingId);
          const statusColors = {
            'submitted': '#007bff',
            'counter_submitted': '#ffc107', 
            'accepted': '#28a745',
            'rejected': '#dc3545',
            'countered': '#fd7e14'
          };
          
          return `
            <div class="offer-item" data-offer-id="${offer.id}">
              <div class="offer-header">
                <div class="offer-property">
                  <h4>${listing ? listing.title : 'Property Not Found'}</h4>
                  <div class="offer-location">${listing ? `${listing.city}, ${listing.province}` : ''}</div>
                </div>
                <div class="offer-status" style="background-color: ${statusColors[offer.status] || '#6c757d'}">
                  ${offer.status.replace('_', ' ').toUpperCase()}
                </div>
              </div>
              
              <div class="offer-details">
                <div class="offer-amount">
                  <strong>Offer: ${fmt(offer.price)}</strong>
                  ${offer.earnestMoney ? `<span class="earnest">Earnest: ${fmt(offer.earnestMoney)}</span>` : ''}
                </div>
                <div class="offer-date">Submitted: ${new Date(offer.submittedAt).toLocaleDateString()}</div>
                ${offer.closingDate ? `<div class="closing-date">Closing: ${new Date(offer.closingDate).toLocaleDateString()}</div>` : ''}
              </div>
              
              ${offer.contingencies ? `
                <div class="offer-contingencies">
                  <strong>Contingencies:</strong>
                  ${Object.entries(offer.contingencies).filter(([key, value]) => value.enabled).map(([key, value]) => 
                    `<span class="contingency-tag">${key} (${value.days}d)</span>`
                  ).join(' ')}
                </div>
              ` : ''}
              
              <div class="offer-actions">
                <button class="btn btn-sm" onclick="viewOfferDetails(${offer.id})">View Details</button>
                ${offer.status === 'counter_submitted' ? `<button class="btn primary btn-sm" onclick="respondToCounter(${offer.id})">Respond to Counter</button>` : ''}
                ${offer.status === 'accepted' ? `<button class="btn success btn-sm" onclick="viewPSAContract(${offer.id})">View Contract</button>` : ''}
                ${(['submitted', 'counter_submitted'].includes(offer.status)) ? `<button class="btn ghost btn-sm" onclick="withdrawOffer(${offer.id})">Withdraw</button>` : ''}
              </div>
            </div>
          `;
        }).join('')}
      </div>
      
      <div class="modal-actions">
        <button class="btn ghost modal-close">Close</button>
      </div>
    </div>
  `;
  
  ModalManager.setupModalCloseButtons('myOffersModal');
}

// Create My Bids Modal
function createMyBidsModal(bids) {
  let modal = $('#myBidsModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'myBidsModal';
    modal.className = 'modal hidden';
    document.body.appendChild(modal);
  }
  
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 700px;">
      <button class="modal-close">✕</button>
      <h3>🏛️ My Auction Bids (${bids.length})</h3>
      
      <div class="bids-list">
        ${bids.map(bid => {
          const listing = state.listings.find(l => l.id === bid.listingId);
          const isWinning = bid.amount === Math.max(...bids.filter(b => b.listingId === bid.listingId).map(b => b.amount));
          
          return `
            <div class="bid-item ${isWinning ? 'winning-bid' : ''}" data-bid-id="${bid.id}">
              <div class="bid-header">
                <div class="bid-property">
                  <h4>${listing ? listing.title : 'Property Not Found'}</h4>
                  <div class="bid-location">${listing ? `${listing.city}, ${listing.province}` : ''}</div>
                </div>
                <div class="bid-status ${isWinning ? 'winning' : 'outbid'}">
                  ${isWinning ? '🏆 WINNING' : '📉 OUTBID'}
                </div>
              </div>
              
              <div class="bid-details">
                <div class="bid-amount">
                  <strong>Your Bid: ${fmt(bid.amount)}</strong>
                </div>
                <div class="bid-date">Placed: ${new Date(bid.timestamp).toLocaleDateString()}</div>
                ${listing && listing.auctionEnds ? `
                  <div class="auction-end">Ends: ${new Date(listing.auctionEnds).toLocaleDateString()}</div>
                ` : ''}
              </div>
              
              <div class="bid-actions">
                <button class="btn btn-sm" onclick="viewAuctionDetails(${bid.listingId})">View Auction</button>
                ${!isWinning ? `<button class="btn primary btn-sm" onclick="placeBidFromHistory(${bid.listingId})">Place Higher Bid</button>` : ''}
              </div>
            </div>
          `;
        }).join('')}
      </div>
      
      <div class="modal-actions">
        <button class="btn ghost modal-close">Close</button>
      </div>
    </div>
  `;
  
  ModalManager.setupModalCloseButtons('myBidsModal');
}

// Create My Contracts Modal
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
      <h3>📄 My PSA Contracts (${contracts.length})</h3>
      
      <div class="contracts-list">
        ${contracts.map(contract => {
          const listing = state.listings.find(l => l.id === contract.listingId);
          const statusColors = {
            'generated': '#6c757d',
            'partially_signed': '#ffc107',
            'fully_executed': '#28a745',
            'cancelled': '#dc3545'
          };
          
          const buyerSigned = contract.signatures.find(s => s.signer === state.user.email);
          const sellerSigned = contract.signatures.find(s => s.role === 'seller');
          
          return `
            <div class="contract-item" data-contract-id="${contract.id}">
              <div class="contract-header">
                <div class="contract-property">
                  <h4>PSA Contract #${contract.id}</h4>
                  <div class="contract-title">${listing ? listing.title : 'Property Not Found'}</div>
                  <div class="contract-location">${listing ? `${listing.city}, ${listing.province}` : ''}</div>
                </div>
                <div class="contract-status" style="background-color: ${statusColors[contract.status] || '#6c757d'}">
                  ${contract.status.replace('_', ' ').toUpperCase()}
                </div>
              </div>
              
              <div class="contract-details">
                <div class="contract-amount">
                  <strong>Purchase Price: ${fmt(contract.purchasePrice)}</strong>
                  ${contract.earnestMoney ? `<span class="earnest">Earnest: ${fmt(contract.earnestMoney)}</span>` : ''}
                </div>
                <div class="contract-dates">
                  <div>Generated: ${new Date(contract.generatedAt).toLocaleDateString()}</div>
                  ${contract.closingDate ? `<div>Closing Date: ${new Date(contract.closingDate).toLocaleDateString()}</div>` : ''}
                  ${contract.executedAt ? `<div>Executed: ${new Date(contract.executedAt).toLocaleDateString()}</div>` : ''}
                </div>
              </div>
              
              <div class="contract-signatures">
                <div class="signature-status">
                  <div class="signature-item ${buyerSigned ? 'signed' : 'pending'}">
                    ${buyerSigned ? '✅' : '⏳'} Buyer Signature ${buyerSigned ? `(${new Date(buyerSigned.signedAt).toLocaleDateString()})` : '(Pending)'}
                  </div>
                  <div class="signature-item ${sellerSigned ? 'signed' : 'pending'}">
                    ${sellerSigned ? '✅' : '⏳'} Seller Signature ${sellerSigned ? `(${new Date(sellerSigned.signedAt).toLocaleDateString()})` : '(Pending)'}
                  </div>
                </div>
              </div>
              
              ${contract.contingencies ? `
                <div class="contract-contingencies">
                  <strong>Contingencies:</strong>
                  ${Object.entries(contract.contingencies).filter(([key, value]) => value.enabled).map(([key, value]) => 
                    `<span class="contingency-tag">${key} (${value.days} days)</span>`
                  ).join(' ')}
                </div>
              ` : ''}
              
              <div class="contract-actions">
                <button class="btn btn-sm" onclick="viewFullContract(${contract.id})">View Full Contract</button>
                <button class="btn btn-sm" onclick="downloadContract(${contract.id})">Download PDF</button>
                ${!buyerSigned && contract.status !== 'cancelled' ? `<button class="btn primary btn-sm" onclick="signContractAsBuyer(${contract.id})">Sign Contract</button>` : ''}
                ${contract.status === 'fully_executed' ? `<button class="btn success btn-sm" onclick="viewEscrowAccount(${contract.id})">View Escrow</button>` : ''}
              </div>
            </div>
          `;
        }).join('')}
      </div>
      
      <div class="modal-actions">
        <button class="btn ghost modal-close">Close</button>
      </div>
    </div>
  `;
  
  ModalManager.setupModalCloseButtons('myContractsModal');
}

/* ===== ENHANCED PSA CONTRACT FUNCTIONS ===== */

// View offer details
function viewOfferDetails(offerId) {
  const offer = state.offers.find(o => o.id === offerId);
  if (!offer) return;
  
  const listing = state.listings.find(l => l.id === offer.listingId);
  
  ModalManager.showAlert({
    title: `Offer Details #${offer.id}`,
    message: `
      <div class="offer-details-modal">
        <h4>${listing ? listing.title : 'Property'}</h4>
        <div><strong>Offer Amount:</strong> ${fmt(offer.price)}</div>
        <div><strong>Earnest Money:</strong> ${fmt(offer.earnestMoney)}</div>
        <div><strong>Status:</strong> ${offer.status.replace('_', ' ').toUpperCase()}</div>
        <div><strong>Submitted:</strong> ${new Date(offer.submittedAt).toLocaleString()}</div>
        ${offer.closingDate ? `<div><strong>Closing Date:</strong> ${new Date(offer.closingDate).toLocaleDateString()}</div>` : ''}
        
        ${offer.contingencies ? `
          <div class="contingencies-section">
            <strong>Contingencies:</strong><br>
            ${Object.entries(offer.contingencies).filter(([key, value]) => value.enabled).map(([key, value]) => 
              `• ${key.charAt(0).toUpperCase() + key.slice(1)}: ${value.days} days`
            ).join('<br>')}
          </div>
        ` : ''}
        
        ${offer.inclusions ? `<div><strong>Inclusions:</strong> ${offer.inclusions}</div>` : ''}
        ${offer.exclusions ? `<div><strong>Exclusions:</strong> ${offer.exclusions}</div>` : ''}
        ${offer.specialTerms ? `<div><strong>Special Terms:</strong> ${offer.specialTerms}</div>` : ''}
        ${offer.buyerComments ? `<div><strong>Comments:</strong> ${offer.buyerComments}</div>` : ''}
        
        ${offer.history ? `
          <div class="offer-history">
            <strong>History:</strong><br>
            ${offer.history.map(h => 
              `• ${new Date(h.timestamp).toLocaleString()}: ${h.details}`
            ).join('<br>')}
          </div>
        ` : ''}
      </div>
    `
  });
}

// Respond to counter offer
function respondToCounter(offerId) {
  const offer = state.offers.find(o => o.id === offerId);
  if (!offer) return;
  
  // Show counter offer modal with accept/reject options
  showCounterOfferModal(offer);
}

// Withdraw offer
function withdrawOffer(offerId) {
  ModalManager.showConfirm({
    title: 'Withdraw Offer',
    message: 'Are you sure you want to withdraw this offer? This action cannot be undone.',
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
        notify('Offer withdrawn successfully');
        
        // Refresh the offers modal
        ModalManager.closeModal('myOffersModal');
        setTimeout(() => openMyOffers(), 100);
      }
    }
  });
}

// View PSA Contract from offer
function viewPSAContract(offerId) {
  const contract = state.contracts.find(c => c.offerId === offerId);
  if (contract) {
    viewFullContract(contract.id);
  } else {
    ModalManager.showAlert({
      title: 'Contract Not Found',
      message: 'PSA contract has not been generated for this offer yet.'
    });
  }
}

// View auction details from bid history
function viewAuctionDetails(listingId) {
  openListing(listingId);
  ModalManager.closeModal('myBidsModal');
}

// Place higher bid from history
function placeBidFromHistory(listingId) {
  const listing = state.listings.find(l => l.id === listingId);
  if (listing && listing.type === 'auction') {
    ModalManager.closeModal('myBidsModal');
    openAuction(listingId);
  }
}

// View full contract details
function viewFullContract(contractId) {
  const contract = state.contracts.find(c => c.id === contractId);
  if (!contract) return;
  
  const listing = state.listings.find(l => l.id === contract.listingId);
  
  // Create comprehensive contract view modal
  let modal = $('#contractViewModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'contractViewModal';
    modal.className = 'modal hidden';
    document.body.appendChild(modal);
  }
  
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 900px; max-height: 80vh; overflow-y: auto;">
      <button class="modal-close">✕</button>
      <div class="contract-document">
        <div class="contract-header-doc">
          <h2>PURCHASE AND SALE AGREEMENT</h2>
          <div class="contract-number">Contract #${contract.id}</div>
        </div>
        
        <div class="contract-section">
          <h3>PARTIES</h3>
          <div><strong>BUYER:</strong> ${contract.buyer}</div>
          <div><strong>SELLER:</strong> ${contract.seller}</div>
        </div>
        
        <div class="contract-section">
          <h3>PROPERTY DESCRIPTION</h3>
          <div><strong>Property:</strong> ${contract.property.title}</div>
          <div><strong>Description:</strong> ${contract.property.description}</div>
          <div><strong>Area:</strong> ${contract.property.area} square meters</div>
          <div><strong>Zoning:</strong> ${contract.property.zoning}</div>
          <div><strong>Location:</strong> ${contract.property.location}</div>
          ${contract.property.coordinates ? `<div><strong>Coordinates:</strong> ${contract.property.coordinates}</div>` : ''}
        </div>
        
        <div class="contract-section">
          <h3>FINANCIAL TERMS</h3>
          <div><strong>Purchase Price:</strong> ${fmt(contract.purchasePrice)}</div>
          <div><strong>Earnest Money:</strong> ${fmt(contract.earnestMoney)}</div>
          <div><strong>Balance Due at Closing:</strong> ${fmt(contract.purchasePrice - contract.earnestMoney)}</div>
          ${contract.closingDate ? `<div><strong>Closing Date:</strong> ${new Date(contract.closingDate).toLocaleDateString()}</div>` : ''}
        </div>
        
        ${contract.contingencies && Object.values(contract.contingencies).some(c => c.enabled) ? `
          <div class="contract-section">
            <h3>CONTINGENCIES</h3>
            ${Object.entries(contract.contingencies).filter(([key, value]) => value.enabled).map(([key, value]) => 
              `<div><strong>${key.charAt(0).toUpperCase() + key.slice(1)} Contingency:</strong> ${value.days} days from acceptance</div>`
            ).join('')}
          </div>
        ` : ''}
        
        ${contract.inclusions ? `
          <div class="contract-section">
            <h3>INCLUSIONS</h3>
            <div>${contract.inclusions}</div>
          </div>
        ` : ''}
        
        ${contract.exclusions ? `
          <div class="contract-section">
            <h3>EXCLUSIONS</h3>
            <div>${contract.exclusions}</div>
          </div>
        ` : ''}
        
        ${contract.specialTerms ? `
          <div class="contract-section">
            <h3>SPECIAL TERMS AND CONDITIONS</h3>
            <div>${contract.specialTerms}</div>
          </div>
        ` : ''}
        
        <div class="contract-section">
          <h3>SIGNATURES</h3>
          <div class="signature-block">
            ${contract.signatures.map(sig => `
              <div class="signature-entry">
                <div><strong>${sig.role.toUpperCase()}:</strong> ${sig.signer}</div>
                <div>Signed: ${new Date(sig.signedAt).toLocaleString()}</div>
                <div>IP Address: ${sig.ipAddress}</div>
                <div class="signature-hash">Hash: ${sig.signatureHash}</div>
              </div>
            `).join('')}
          </div>
        </div>
        
        <div class="contract-section">
          <h3>CONTRACT STATUS</h3>
          <div><strong>Status:</strong> ${contract.status.replace('_', ' ').toUpperCase()}</div>
          <div><strong>Generated:</strong> ${new Date(contract.generatedAt).toLocaleString()}</div>
          ${contract.executedAt ? `<div><strong>Fully Executed:</strong> ${new Date(contract.executedAt).toLocaleString()}</div>` : ''}
          <div><strong>Signatures Collected:</strong> ${contract.signaturesCollected} of ${contract.signaturesRequired}</div>
        </div>
      </div>
      
      <div class="modal-actions">
        <button class="btn ghost modal-close">Close</button>
        <button class="btn" onclick="downloadContract(${contract.id})">Download PDF</button>
        ${!contract.signatures.find(s => s.signer === state.user.email) && contract.status !== 'cancelled' ? 
          `<button class="btn primary" onclick="signContractAsBuyer(${contract.id})">Sign Contract</button>` : ''}
      </div>
    </div>
  `;
  
  ModalManager.setupModalCloseButtons('contractViewModal');
  ModalManager.openModal('contractViewModal');
}

// Download contract as text file (simulated PDF)
function downloadContract(contractId) {
  const contract = state.contracts.find(c => c.id === contractId);
  if (!contract) return;
  
  const listing = state.listings.find(l => l.id === contract.listingId);
  
  let contractText = `TERRATRADE PURCHASE AND SALE AGREEMENT\n`;
  contractText += `Contract #${contract.id}\n`;
  contractText += `Generated: ${new Date(contract.generatedAt).toLocaleString()}\n\n`;
  contractText += `PARTIES:\n`;
  contractText += `Buyer: ${contract.buyer}\n`;
  contractText += `Seller: ${contract.seller}\n\n`;
  contractText += `PROPERTY DESCRIPTION:\n`;
  contractText += `Property: ${contract.property.title}\n`;
  contractText += `Description: ${contract.property.description}\n`;
  contractText += `Area: ${contract.property.area} square meters\n`;
  contractText += `Zoning: ${contract.property.zoning}\n`;
  contractText += `Location: ${contract.property.location}\n\n`;
  contractText += `FINANCIAL TERMS:\n`;
  contractText += `Purchase Price: ${fmt(contract.purchasePrice)}\n`;
  contractText += `Earnest Money: ${fmt(contract.earnestMoney)}\n`;
  contractText += `Balance Due: ${fmt(contract.purchasePrice - contract.earnestMoney)}\n`;
  if (contract.closingDate) contractText += `Closing Date: ${new Date(contract.closingDate).toLocaleDateString()}\n`;
  contractText += `\n`;
  
  if (contract.contingencies && Object.values(contract.contingencies).some(c => c.enabled)) {
    contractText += `CONTINGENCIES:\n`;
    Object.entries(contract.contingencies).filter(([key, value]) => value.enabled).forEach(([key, value]) => {
      contractText += `${key.charAt(0).toUpperCase() + key.slice(1)}: ${value.days} days\n`;
    });
    contractText += `\n`;
  }
  
  if (contract.inclusions) {
    contractText += `INCLUSIONS:\n${contract.inclusions}\n\n`;
  }
  
  if (contract.exclusions) {
    contractText += `EXCLUSIONS:\n${contract.exclusions}\n\n`;
  }
  
  if (contract.specialTerms) {
    contractText += `SPECIAL TERMS:\n${contract.specialTerms}\n\n`;
  }
  
  contractText += `SIGNATURES:\n`;
  contract.signatures.forEach(sig => {
    contractText += `${sig.role.toUpperCase()}: ${sig.signer}\n`;
    contractText += `Signed: ${new Date(sig.signedAt).toLocaleString()}\n`;
    contractText += `Signature Hash: ${sig.signatureHash}\n\n`;
  });
  
  contractText += `CONTRACT STATUS: ${contract.status.replace('_', ' ').toUpperCase()}\n`;
  if (contract.executedAt) {
    contractText += `FULLY EXECUTED: ${new Date(contract.executedAt).toLocaleString()}\n`;
  }
  
  // Create download
  const blob = new Blob([contractText], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `PSA_Contract_${contract.id}_${Date.now()}.txt`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  
  notify('Contract downloaded');
}

// Sign contract as buyer
function signContractAsBuyer(contractId) {
  ModalManager.showConfirm({
    title: 'Sign Contract',
    message: 'By signing this contract, you agree to all terms and conditions. This action cannot be undone.',
    confirmText: 'Sign Contract',
    cancelText: 'Cancel',
    onConfirm: () => {
      signPSAContract(contractId, state.user.email, 'buyer');
      notify('Contract signed successfully!');
      
      // Refresh contracts modal if open
      if (ModalManager.isModalOpen('myContractsModal')) {
        ModalManager.closeModal('myContractsModal');
        setTimeout(() => openMyContracts(), 100);
      }
      
      // Close contract view modal
      ModalManager.closeModal('contractViewModal');
    }
  });
}

// View escrow account from contract
function viewEscrowAccount(contractId) {
  const contract = state.contracts.find(c => c.id === contractId);
  if (!contract) return;
  
  // Find the associated offer to get escrow account
  const offer = state.offers.find(o => o.id === contract.offerId);
  if (!offer) return;
  
  const escrowAccount = state.escrowAccounts.find(e => e.offerId === offer.id);
  if (escrowAccount) {
    state.currentEscrowAccount = escrowAccount;
    const listing = state.listings.find(l => l.id === escrowAccount.listingId);
    populateEnhancedEscrowModal(escrowAccount, listing);
    ModalManager.closeModal('myContractsModal');
    ModalManager.openModal('enhancedEscrowModal');
  } else {
    ModalManager.showAlert({
      title: 'Escrow Not Found',
      message: 'Escrow account has not been created for this contract yet.'
    });
  }
}
