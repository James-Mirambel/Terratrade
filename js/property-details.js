/**
 * Property Details Page JavaScript
 * TerraTrade Land Trading System
 */

console.log('Property details JS loaded!');

// Sample property photos
const propertyPhotos = [
    'https://via.placeholder.com/800x500/4CAF50/ffffff?text=Property+Photo+1',
    'https://via.placeholder.com/800x500/2196F3/ffffff?text=Property+Photo+2',
    'https://via.placeholder.com/800x500/FF9800/ffffff?text=Property+Photo+3',
    'https://via.placeholder.com/800x500/9C27B0/ffffff?text=Property+Photo+4',
    'https://via.placeholder.com/800x500/F44336/ffffff?text=Property+Photo+5'
];

let currentPhotoIndex = 0;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeContactForm();
    initializeMakeOffer();
    initializeShareButtons();
    initializeGalleryTabs();
});

// Tab Navigation
function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.getAttribute('data-tab');

            // Remove active class from all tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked tab
            button.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        });
    });
}

// Photo Gallery
function initializePhotoGallery() {
    const mainPhoto = document.getElementById('mainPhoto');
    const thumbnails = document.querySelectorAll('.thumbnail');
    const prevBtn = document.querySelector('.gallery-nav.prev');
    const nextBtn = document.querySelector('.gallery-nav.next');
    const fullscreenBtn = document.querySelector('.fullscreen-btn');

    // Thumbnail click
    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', () => {
            showPhoto(index);
        });
    });

    // Previous button
    prevBtn.addEventListener('click', () => {
        currentPhotoIndex = (currentPhotoIndex - 1 + propertyPhotos.length) % propertyPhotos.length;
        showPhoto(currentPhotoIndex);
    });

    // Next button
    nextBtn.addEventListener('click', () => {
        currentPhotoIndex = (currentPhotoIndex + 1) % propertyPhotos.length;
        showPhoto(currentPhotoIndex);
    });

    // Fullscreen button
    fullscreenBtn.addEventListener('click', () => {
        if (mainPhoto.requestFullscreen) {
            mainPhoto.requestFullscreen();
        } else if (mainPhoto.webkitRequestFullscreen) {
            mainPhoto.webkitRequestFullscreen();
        } else if (mainPhoto.msRequestFullscreen) {
            mainPhoto.msRequestFullscreen();
        }
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevBtn.click();
        } else if (e.key === 'ArrowRight') {
            nextBtn.click();
        }
    });
}

function showPhoto(index) {
    currentPhotoIndex = index;
    const mainPhoto = document.getElementById('mainPhoto');
    const thumbnails = document.querySelectorAll('.thumbnail');

    // Update main photo
    mainPhoto.src = propertyPhotos[index];

    // Update active thumbnail
    thumbnails.forEach((thumb, i) => {
        if (i === index) {
            thumb.classList.add('active');
        } else {
            thumb.classList.remove('active');
        }
    });

    // Smooth fade effect
    mainPhoto.style.opacity = '0';
    setTimeout(() => {
        mainPhoto.style.opacity = '1';
    }, 100);
}

// Contact Form
function initializeContactForm() {
    const contactForm = document.getElementById('contactForm');
    
    if (!contactForm) {
        console.log('Contact form not present (seller view)');
        return;
    }
    
    console.log('Contact form handler attached!');

    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        console.log('Form submitted!');

        const formData = new FormData(contactForm);
        const data = Object.fromEntries(formData);
        
        // Prepare message data for API
        const messageData = {
            receiver_id: parseInt(data.receiver_id),
            property_id: parseInt(data.property_id),
            message: `${data.message}\n\nContact Info:\nName: ${data.first_name} ${data.last_name}\nEmail: ${data.email}\nPhone: ${data.phone}\n\nPreferred Contact: ${data.contact_email ? 'Email ' : ''}${data.contact_phone ? 'Phone ' : ''}${data.contact_text ? 'Text' : ''}`
        };

        // Debug: Log the message data
        console.log('Sending message:', messageData);
        
        try {
            const response = await fetch('api/messages/send-simple.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(messageData)
            });
            
            console.log('Response status:', response.status);
            
            // Get response text first to see what we're getting
            const responseText = await response.text();
            console.log('Response text:', responseText);
            
            // Try to parse as JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Failed to parse JSON:', parseError);
                console.error('Response was:', responseText.substring(0, 500));
                throw new Error('Server returned invalid response. Check console for details.');
            }
            
            console.log('Response data:', result);
            
            if (result.success) {
                alert('Message sent successfully! The property owner will contact you soon.');
                contactForm.reset();
                // Optionally redirect to messaging page
                // window.location.href = '/messaging.php';
            } else {
                if (response.status === 401) {
                    alert('You must be logged in to send messages. Redirecting to login...');
                    window.location.href = '/index.php#login';
                } else {
                    alert('Failed to send message: ' + (result.error || 'Unknown error'));
                }
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('Failed to send message. Please try again. Error: ' + error.message);
        }
    });
}

// Photo Tabs (Photos/Maps)
const photoTabButtons = document.querySelectorAll('.photo-tab-btn');
photoTabButtons.forEach(button => {
    button.addEventListener('click', () => {
        const tabType = button.getAttribute('data-photo-tab');

        // Remove active class from all photo tabs
        photoTabButtons.forEach(btn => btn.classList.remove('active'));

        // Add active class to clicked tab
        button.classList.add('active');

        // In production, this would load different content
        console.log('Switching to:', tabType);
    });
});

// Smooth scroll for internal links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add smooth transition to main photo
const mainPhoto = document.getElementById('mainPhoto');
if (mainPhoto) {
    mainPhoto.style.transition = 'opacity 0.3s ease';
}

// Make Offer Functionality
function initializeMakeOffer() {
    const makeOfferBtn = document.getElementById('makeOfferBtn');
    
    if (makeOfferBtn) {
        makeOfferBtn.addEventListener('click', showMakeOfferModal);
    }
}

function showMakeOfferModal() {
    // Get property details from window data
    const propertyData = window.propertyData;
    const propertyTitle = propertyData ? propertyData.title : 'Property';
    const propertyPrice = propertyData ? `₱${parseFloat(propertyData.price || 0).toLocaleString()}` : '₱0';
    const propertyId = propertyData ? propertyData.id : new URLSearchParams(window.location.search).get('id');
    
    // Create modal HTML
    const modalHTML = `
        <div class="modal-overlay" id="makeOfferModal">
            <div class="modal-container offer-modal">
                <div class="modal-header">
                    <h2>💰 Make an Offer</h2>
                    <button class="modal-close" onclick="closeMakeOfferModal()">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div class="property-summary">
                        <h3>${propertyTitle}</h3>
                        <p class="listed-price">Listed Price: <strong>${propertyPrice}</strong></p>
                    </div>
                    
                    <form id="makeOfferForm" enctype="multipart/form-data">
                        <input type="hidden" name="property_id" value="${propertyId}">
                        
                        <!-- Buyer Information (Auto-filled) -->
                        <div class="form-section">
                            <h4>👤 Buyer Information</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name*</label>
                                    <input type="text" name="buyer_name" id="buyerName" required>
                                </div>
                                <div class="form-group">
                                    <label>Email*</label>
                                    <input type="email" name="buyer_email" id="buyerEmail" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Phone Number*</label>
                                <input type="tel" name="buyer_phone" id="buyerPhone" required>
                            </div>
                        </div>
                        
                        <!-- Offer Details -->
                        <div class="form-section">
                            <h4>💵 Offer Details</h4>
                            <div class="form-group">
                                <label>Your Offer Price (₱)*</label>
                                <input type="number" name="offer_price" id="offerPrice" required 
                                       placeholder="Enter your offer amount" min="1" step="0.01">
                                <small class="form-hint">Enter the amount you're willing to pay</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Payment Method*</label>
                                <select name="payment_method" required>
                                    <option value="">Select payment method</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_financing">Bank Financing</option>
                                    <option value="installment">Installment</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Required Documents -->
                        <div class="form-section">
                            <h4>📎 Required Documents</h4>
                            
                            <div class="form-group">
                                <label>Valid ID (Government-issued)*</label>
                                <input type="file" name="valid_id" id="validId" required 
                                       accept=".pdf,.jpg,.jpeg,.png" onchange="previewFile(this, 'validIdPreview')">
                                <small class="form-hint">Driver's License, Passport, National ID, etc. (Max 5MB)</small>
                                <div id="validIdPreview" class="file-preview"></div>
                            </div>
                            
                            <div class="form-group">
                                <label>Proof of Income/Funds (Optional but recommended)</label>
                                <input type="file" name="proof_of_funds" id="proofOfFunds" 
                                       accept=".pdf,.jpg,.jpeg,.png" onchange="previewFile(this, 'proofPreview')">
                                <small class="form-hint">Bank Statement, Certificate of Employment, ITR (Max 5MB)</small>
                                <div id="proofPreview" class="file-preview"></div>
                            </div>
                        </div>
                        
                        <!-- Message to Seller -->
                        <div class="form-section">
                            <h4>💬 Message to Seller</h4>
                            <div class="form-group">
                                <label>Additional Information (Optional)</label>
                                <textarea name="message" rows="4" 
                                          placeholder="Introduce yourself, explain your offer, mention any special terms..."></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="closeMakeOfferModal()">Cancel</button>
                            <button type="submit" class="btn-primary">Submit Offer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Load user info
    loadBuyerInfo();
    
    // Attach form submit handler
    document.getElementById('makeOfferForm').addEventListener('submit', submitOffer);
}

async function loadBuyerInfo() {
    try {
        // Fetch current user info
        const response = await fetch('api/user/current.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.user) {
            document.getElementById('buyerName').value = data.user.full_name || '';
            document.getElementById('buyerEmail').value = data.user.email || '';
            document.getElementById('buyerPhone').value = data.user.phone || '';
        }
    } catch (error) {
        console.error('Error loading buyer info:', error);
    }
}

function previewFile(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        // Check file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            input.value = '';
            return;
        }
        
        preview.innerHTML = `
            <div class="file-info">
                <span class="file-icon">📄</span>
                <span class="file-name">${file.name}</span>
                <span class="file-size">(${(file.size / 1024).toFixed(2)} KB)</span>
            </div>
        `;
    }
}

async function submitOffer(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    try {
        console.log('Submitting offer with data:', Object.fromEntries(formData));
        
        // First test if API is reachable
        const pingResponse = await fetch('api/offers/ping.php', {
            method: 'GET',
            credentials: 'include'
        });
        console.log('Ping test:', await pingResponse.text());
        
        const response = await fetch('api/offers/submit.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Get response text first to see what we're getting
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Failed to parse JSON:', parseError);
            console.error('Response was:', responseText.substring(0, 500));
            alert('❌ Server returned invalid response. Check console for details.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Offer';
            return;
        }
        
        console.log('Parsed response:', data);
        
        if (data.success) {
            alert('✅ ' + data.message + ' The seller will review your offer.');
            closeMakeOfferModal();
            // Optionally redirect to offers page
            // window.location.href = 'view-offers.php';
        } else {
            alert('❌ Failed to submit offer: ' + (data.error || 'Unknown error'));
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Offer';
        }
    } catch (error) {
        console.error('Error submitting offer:', error);
        alert('❌ Failed to submit offer. Please try again. Error: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Offer';
    }
}

function closeMakeOfferModal() {
    const modal = document.getElementById('makeOfferModal');
    if (modal) {
        modal.remove();
    }
}

// Share functionality
function initializeShareButtons() {
    console.log('Share buttons initialized');
}

function shareProperty(platform) {
    const url = window.location.href;
    const title = document.querySelector('.property-title')?.textContent || 'Check out this property';
    
    let shareUrl = '';
    
    switch(platform) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }
}

function copyLink() {
    const url = window.location.href;
    
    navigator.clipboard.writeText(url).then(() => {
        alert('✅ Link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('❌ Failed to copy link');
    });
}

// Gallery Functions
let currentImageIndex = 0;
const galleryImages = [];

function initializeGalleryTabs() {
    const galleryTabs = document.querySelectorAll('.gallery-tab');
    
    // Collect all thumbnail images
    document.querySelectorAll('.thumbnail img').forEach(img => {
        galleryImages.push(img.src.replace('200x150', '1200x600'));
    });
    
    galleryTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const tabName = this.getAttribute('data-tab');
            
            // Remove active class from all tabs
            galleryTabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab contents
            document.querySelectorAll('.gallery-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
        });
    });
}

function changeMainImage(thumbnail, index) {
    const mainImage = document.getElementById('mainGalleryImage');
    const thumbnailImage = thumbnail.querySelector('img');
    
    currentImageIndex = index;
    
    // Update main image
    mainImage.src = thumbnailImage.src.replace('200x150', '1200x600');
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    thumbnail.classList.add('active');
}

function previousImage() {
    const thumbnails = document.querySelectorAll('.thumbnail');
    currentImageIndex = (currentImageIndex - 1 + thumbnails.length) % thumbnails.length;
    thumbnails[currentImageIndex].click();
}

function nextImage() {
    const thumbnails = document.querySelectorAll('.thumbnail');
    currentImageIndex = (currentImageIndex + 1) % thumbnails.length;
    thumbnails[currentImageIndex].click();
}

function toggleFullscreen() {
    const mainPhoto = document.querySelector('.main-photo');
    
    if (!document.fullscreenElement) {
        mainPhoto.requestFullscreen().catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

// Header Menu Toggle
function toggleMenu() {
    const menu = document.getElementById('headerMenu');
    menu.classList.toggle('show');
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('headerMenu');
    const menuBtn = document.querySelector('.menu-btn');
    
    if (menu && menuBtn && !menu.contains(event.target) && !menuBtn.contains(event.target)) {
        menu.classList.remove('show');
    }
});
