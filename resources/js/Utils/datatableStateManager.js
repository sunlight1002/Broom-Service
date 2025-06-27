/**
 * DataTables State Management Utility
 * 
 * This utility provides a centralized way to manage DataTables state persistence
 * across the application. It ensures each table has a unique identifier and
 * proper state management.
 */

// Table ID registry to ensure uniqueness
const TABLE_IDS = {
    // Admin Tables
    CONTRACTS: 'admin_contracts_table',
    ALL_WORKERS: 'admin_all_workers_table',
    TOTAL_JOBS: 'admin_total_jobs_table',
    LEADS: 'admin_leads_table',
    SCHEDULE: 'admin_schedule_table',
    CLIENTS: 'admin_clients_table',
    OFFER_PRICE: 'admin_offer_price_table',
    WORKER_LEADS: 'admin_worker_leads_table',
    WORKER_REFUNDS: 'admin_worker_refunds_table',
    SCHEDULE_CHANGES: 'admin_schedule_changes_table',
    PENDING_REQUESTS: 'admin_pending_requests_table',
    WORKER_PENDING_REQUESTS: 'admin_worker_pending_requests_table',
    EXPENSES: 'admin_expenses_table',
    
    // Worker Tables
    WORKER_SICK_LEAVES: 'worker_sick_leaves_table',
    WORKER_REFUND_CLAIMS: 'worker_refund_claims_table',
    WORKER_ADVANCE_LOANS: 'worker_advance_loans_table',
    
    // Client Tables
    CLIENT_CONTRACTS: 'client_contracts_table',
    CLIENT_OFFERED_PRICE: 'client_offered_price_table',
    
    // Settings Tables
    HOLIDAYS: 'admin_holidays_table',
    MANPOWER_COMPANIES: 'admin_manpower_companies_table',
    CONFLICTS: 'admin_conflicts_table',
};

/**
 * Get DataTables state configuration
 * @param {string} tableId - Unique table identifier
 * @param {Object} options - Additional options
 * @returns {Object} DataTables state configuration
 */
export const getDataTableStateConfig = (tableId, options = {}) => {
    return {
        stateSave: true,
        stateLoadParams: function (settings, data) {
            console.log(`Loading table state for: ${settings.sTableId}`);
            if (options.onStateLoad) {
                options.onStateLoad(settings, data);
            }
        },
        stateSaveParams: function (settings, data) {
            console.log(`Saving table state for: ${settings.sTableId}`);
            if (options.onStateSave) {
                options.onStateSave(settings, data);
            }
        },
        sTableId: tableId,
        // Optional: Custom state duration (default is 2 hours)
        stateDuration: options.stateDuration || 7200,
    };
};

/**
 * Clear specific table state
 * @param {string} tableId - Table ID to clear state for
 */
export const clearTableState = (tableId) => {
    try {
        const stateKey = `DataTables_${tableId}_/`;
        localStorage.removeItem(stateKey);
        console.log(`Cleared state for table: ${tableId}`);
    } catch (error) {
        console.warn(`Failed to clear state for table ${tableId}:`, error);
    }
};

/**
 * Clear all DataTables states
 */
export const clearAllTableStates = () => {
    try {
        const keys = Object.keys(localStorage);
        const dataTableKeys = keys.filter(key => key.startsWith('DataTables_'));
        dataTableKeys.forEach(key => localStorage.removeItem(key));
        console.log('Cleared all DataTables states');
    } catch (error) {
        console.warn('Failed to clear all DataTables states:', error);
    }
};

/**
 * Get table state info
 * @param {string} tableId - Table ID
 * @returns {Object|null} State information or null if not found
 */
export const getTableStateInfo = (tableId) => {
    try {
        const stateKey = `DataTables_${tableId}_/`;
        const state = localStorage.getItem(stateKey);
        if (state) {
            const parsedState = JSON.parse(state);
            return {
                tableId,
                timestamp: parsedState.time,
                page: parsedState.start / parsedState.length,
                search: parsedState.search,
                order: parsedState.order,
                length: parsedState.length,
            };
        }
        return null;
    } catch (error) {
        console.warn(`Failed to get state info for table ${tableId}:`, error);
        return null;
    }
};

/**
 * Initialize DataTable with state management
 * @param {HTMLElement} tableElement - Table DOM element
 * @param {string} tableId - Unique table identifier
 * @param {Object} config - DataTables configuration
 * @param {Object} options - Additional options
 * @returns {Object} DataTable instance
 */
export const initializeDataTableWithState = (tableElement, tableId, config, options = {}) => {
    const stateConfig = getDataTableStateConfig(tableId, options);
    const fullConfig = {
        ...config,
        ...stateConfig,
    };
    
    return $(tableElement).DataTable(fullConfig);
};

// Export table IDs for use in components
export { TABLE_IDS }; 