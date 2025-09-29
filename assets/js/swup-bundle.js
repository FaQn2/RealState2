
/**
 * Este archivo exporta SWUP y sus plugins para uso global
 */
import Swup from 'swup';
import SwupScrollPlugin from '@swup/scroll-plugin';
import SwupPreloadPlugin from '@swup/preload-plugin';
import SwupProgressPlugin from '@swup/progress-plugin';
import SwupHeadPlugin from '@swup/head-plugin';
import SwupScriptsPlugin from '@swup/scripts-plugin';
import SwupFormsPlugin from '@swup/forms-plugin';

// Export para uso global
window.Swup = Swup;
window.SwupScrollPlugin = SwupScrollPlugin;
window.SwupPreloadPlugin = SwupPreloadPlugin;
window.SwupProgressPlugin = SwupProgressPlugin;
window.SwupHeadPlugin = SwupHeadPlugin;
window.SwupScriptsPlugin = SwupScriptsPlugin;
window.SwupFormsPlugin = SwupFormsPlugin;

