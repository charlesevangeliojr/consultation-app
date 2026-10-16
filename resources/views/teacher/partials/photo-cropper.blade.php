<dialog class="profile-crop-dialog" data-photo-crop-dialog aria-labelledby="crop-photo-heading" aria-describedby="crop-photo-instructions">
    <div class="profile-crop-heading">
        <div><h2 id="crop-photo-heading">Crop profile picture</h2><p id="crop-photo-instructions">Drag to reposition, then adjust the zoom. The circle shows your avatar preview.</p></div>
        <button type="button" data-crop-cancel aria-label="Close photo cropper" class="profile-crop-close">×</button>
    </div>
    <div class="profile-crop-stage">
        <canvas width="512" height="512" tabindex="0" data-crop-canvas aria-label="Photo crop area. Drag to reposition, or use the arrow keys."></canvas>
        <div class="profile-crop-mask" aria-hidden="true"></div>
    </div>
    <p class="profile-crop-status" data-crop-status role="status">Loading picture…</p>
    <div class="profile-crop-zoom">
        <label for="photo-crop-zoom">Zoom</label>
        <input id="photo-crop-zoom" type="range" min="1" max="3" step="0.01" value="1" data-crop-zoom>
        <output for="photo-crop-zoom" data-crop-zoom-value>100%</output>
    </div>
    <div class="profile-crop-actions">
        <button type="button" class="profile-crop-secondary" data-crop-reset>Reset</button>
        <span></span>
        <button type="button" class="profile-crop-secondary" data-crop-cancel>Cancel</button>
        <button type="button" class="profile-crop-primary" data-crop-apply disabled>Use photo</button>
    </div>
</dialog>
