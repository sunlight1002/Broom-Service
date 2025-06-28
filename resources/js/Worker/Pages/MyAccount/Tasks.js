import React, { useState, useEffect, useRef, useCallback } from "react";
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { GrUpgrade } from "react-icons/gr";
import Select from "react-select";
import Moment from "moment";
import { useAlert } from "react-alert";
import CommentModal from '../../../Admin/Pages/TaskManagement/CommentModal';
import '../../../Admin/Pages/TaskManagement/Board.css';
import Sidebar from "../../Layouts/WorkerSidebar";
import FilterButtons from "../../../Components/common/FilterButton";
import Editor from 'react-simple-wysiwyg';
import TaskDetailModal from '../../../Admin/Pages/TaskManagement/TaskModal';
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import "react-super-responsive-table/dist/SuperResponsiveTableStyle.css";

// Add custom styles for the table
const tableStyles = `
    <style>
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .clickable-row:hover {
            background-color: #f8f9fa !important;
        }
        .dt-comment-btn {
            transition: all 0.2s ease;
        }
        .dt-comment-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-weight: 500;
        }
        .custom-row-class {
            border-bottom: 1px solid #dee2e6;
        }
        .custom-cell-class {
            vertical-align: middle;
        }
        .modal-container {
            z-index: 9999 !important;
        }
        .modal-backdrop {
            z-index: 9998 !important;
        }
    </style>
`;

function Tasks() {
    const { t } = useTranslation();
    const [statusOptions, setStatusOptions] = useState([]);
    const [isComModal, setIsComModal] = useState(false);
    const [comment, setComments] = useState('');
    const [taskComments, setTaskComments] = useState([]);
    const [taskName, setTaskName] = useState('');
    const alert = useAlert();
    const [selectedTaskId, setSelectedTaskId] = useState(null);
    const [datePeriod, setDatePeriod] = useState('');
    const [dateRange, setDateRange] = useState({ start: '', end: '' });
    const [isDetailModal, setIsDetailModal] = useState(false);
    const [detailTask, setDetailTask] = useState(null);
    const [detailStatus, setDetailStatus] = useState('');
    const [isEditable, setIsEditable] = useState(false);
    const [statusFilter, setStatusFilter] = useState('All');
    const [search, setSearch] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const tableRef = useRef(null);
    
    // Use refs to store current filter values for DataTable access
    const filterRefs = useRef({
        statusFilter: 'All',
        dateRange: { start: '', end: '' },
        search: ''
    });

    // Update refs when filters change
    useEffect(() => {
        filterRefs.current = {
            statusFilter,
            dateRange,
            search
        };
    }, [statusFilter, dateRange, search]);

    const worker_id = localStorage.getItem("worker-id");

    // Inject custom styles
    useEffect(() => {
        const styleElement = document.createElement('style');
        styleElement.textContent = tableStyles.replace('<style>', '').replace('</style>', '');
        document.head.appendChild(styleElement);
        
        return () => {
            document.head.removeChild(styleElement);
        };
    }, []);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    // Helper to get date range for period
    const getDateRangeForPeriod = (period) => {
        const today = Moment().format('YYYY-MM-DD');
        if (period === 'Day') {
            return { start: today, end: today };
        } else if (period === 'Week') {
            return {
                start: Moment().startOf('week').format('YYYY-MM-DD'),
                end: Moment().endOf('week').format('YYYY-MM-DD'),
            };
        } else if (period === 'Month') {
            return {
                start: Moment().startOf('month').format('YYYY-MM-DD'),
                end: Moment().endOf('month').format('YYYY-MM-DD'),
            };
        }
        return { start: '', end: '' };
    };

    const getTasks = useCallback(async (params = {}) => {
        try {
            // Build query parameters
            const queryParams = {
                page: 1, // Assuming default page 1
                per_page: 10, // Assuming default page size 10
                sort_by: "due_date",
                sort_order: "ASC",
                ...params
            };

            // Add filters only if they have values
            if (statusFilter && statusFilter !== 'All') {
                queryParams.status = statusFilter;
            }
            
            if (search && search.trim()) {
                queryParams.search = search.trim();
            }
            
            if (dateRange.start) {
                queryParams.due_date_start = dateRange.start;
            }
            
            if (dateRange.end) {
                queryParams.due_date_end = dateRange.end;
            }

            const response = await axios.get(`/api/tasks/worker/${worker_id}`, {
                headers,
                params: queryParams
            });
            // Assuming response.data.data contains the tasks
            // You might want to update the state with this data
            console.log(response.data.data);
        } catch (error) {
            console.error('Error fetching tasks:', error);
        }
    }, [statusFilter, search, dateRange, worker_id, headers]);

    useEffect(() => {
        getTasks();
        setStatusOptions([
            { value: "Pending", label: "Pending" },
            { value: "In Progress", label: "In Progress" },
            { value: "Completed", label: "Completed" },
        ]);
    }, []);

    // DataTable initialization
    useEffect(() => {
        if (tableRef.current) {
            const table = $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: `/api/tasks/worker/${worker_id}`,
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("worker-token")
                        );
                    },
                    data: function (d) {
                        // Access current filter values from refs
                        const currentFilters = filterRefs.current;
                        
                        if (currentFilters.statusFilter && currentFilters.statusFilter !== 'All') {
                            d.status = currentFilters.statusFilter;
                        }
                        if (currentFilters.dateRange.start) {
                            d.due_date_start = currentFilters.dateRange.start;
                        }
                        if (currentFilters.dateRange.end) {
                            d.due_date_end = currentFilters.dateRange.end;
                        }
                        if (currentFilters.search && currentFilters.search.trim()) {
                            d.search = currentFilters.search.trim();
                        }
                        return d;
                    },
                    error: function (xhr, error, thrown) {
                        console.error('DataTable error:', error);
                        setError('Failed to load tasks. Please try again.');
                    }
                },
                order: [[3, "desc"]],
                columns: [
                    {
                        title: "No.",
                        data: null,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        title: "Task Name",
                        data: "task_name",
                        name: "task_name"
                    },
                    {
                        title: "Status",
                        data: "status",
                        name: "status",
                        render: function (data, type, row, meta) {
                            const statusLower = data?.toLowerCase() || '';
                            let backgroundColor = '#6c757d';
                            if (statusLower.includes('complete')) {
                                backgroundColor = '#28a745';
                            } else if (statusLower.includes('progress')) {
                                backgroundColor = '#ffc107';
                            }
                            return `<span class=\"status-badge\" style=\"background-color: ${backgroundColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;\">${data}</span>`;
                        }
                    },
                    {
                        title: "Deadline",
                        data: "due_date",
                        name: "due_date",
                        render: function (data, type, row, meta) {
                            return data ? Moment(data).format("YYYY-MM-DD") : "";
                        }
                    },
                    {
                        title: "Comment",
                        data: "comments",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            const commentCount = data ? data.length : 0;
                            return `<button class=\"btn btn-sm btn-light dt-comment-btn\" data-task-id=\"${row.id}\">\n                                <i class=\"fa fa-comment\"></i> ${commentCount}\n                            </button>`;
                        }
                    },
                    {
                        title: "Worker/Team Member",
                        data: null,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            let html = '<div>';
                            if (row.workers && row.workers.length > 0) {
                                html += `<div><strong>Worker:</strong> ${row.workers.map(w => w.name || w.firstname).join(", ")}</div>`;
                            }
                            if (row.users && row.users.length > 0) {
                                html += `<div><strong>Team:</strong> ${row.users.map(u => u.name).join(", ")}</div>`;
                            }
                            html += '</div>';
                            return html;
                        }
                    }
                ],
                ordering: true,
                searching: false,
                responsive: true,
                autoWidth: true,
                width: "100%",
                scrollX: true,
                language: {
                    processing: "Loading tasks...",
                    search: "Search:",
                    lengthMenu: "Show _MENU_ tasks per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ tasks",
                    infoEmpty: "Showing 0 to 0 of 0 tasks",
                    infoFiltered: "(filtered from _MAX_ total tasks)",
                    emptyTable: "No tasks found",
                    zeroRecords: "No tasks match your search criteria"
                },
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass('custom-row-class');
                    $(row).addClass('clickable-row');
                },
                columnDefs: [
                    {
                        targets: '_all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).addClass('custom-cell-class');
                        }
                    }
                ],
                drawCallback: function (settings) {
                    $(tableRef.current).off("click", ".dt-comment-btn").on("click", ".dt-comment-btn", function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        const taskId = $(this).data("task-id");
                        console.log('Comment button clicked for task ID:', taskId);
                        
                        setTimeout(() => {
                            handleEditTask(taskId);
                            setIsComModal(true);
                        }, 100);
                    });

                    $(tableRef.current).off("click", "tbody tr").on("click", "tbody tr", function (e) {
                        if (!$(e.target).closest('.dt-comment-btn').length) {
                            const data = $(tableRef.current).DataTable().row(this).data();
                            if (data) {
                                console.log('Row clicked for task:', data);
                                setDetailTask(data);
                                setDetailStatus(data.status);
                                setIsDetailModal(true);
                            }
                        }
                    });
                }
            });

            return function cleanup() {
                if (tableRef.current && $(tableRef.current).DataTable()) {
                    $(tableRef.current).DataTable().destroy(true);
                }
            };
        }
    }, []);

    // Refresh table when filters change
    useEffect(() => {
        if (tableRef.current && $(tableRef.current).DataTable()) {
            $(tableRef.current).DataTable().ajax.reload();
        }
    }, [statusFilter, dateRange, search]);

    const handleEditTask = async (tid) => {
        console.log('handleEditTask called with task ID:', tid); // Debug log
        try {
            const response = await axios.get(`/api/tasks/${tid}`, { headers });
            console.log('Task details response:', response.data); // Debug log
            setSelectedTaskId(response.data?.id);
            setTaskComments(response.data?.comments);
            setTaskName(response.data?.task_name);
        } catch (error) {
            console.error('Error in handleEditTask:', error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to load task details. Please try again.');
            }
        }
    };

    const handleComment = async () => {
        const data = {
            comment: comment,
            type: "worker"
        };
        try {
            const res = await axios.post(`/api/tasks/${selectedTaskId}/comments`, data, { headers });
            setComments('');
            alert.success(res?.data?.message || 'Comment added successfully!');
            handleEditTask(selectedTaskId);
            // Reload DataTable to update comment count
            if (tableRef.current && $(tableRef.current).DataTable()) {
                $(tableRef.current).DataTable().ajax.reload();
            }
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to add comment. Please try again.');
            }
        }
    };

    const handleAddComment = async (task) => {
        handleEditTask(task.id);
        setIsComModal(true);
    };

    const getInitials = (name) => {
        return name.split(' ').map(part => part[0]).join('');
    };

    const handleDeleteComment = async (cid) => {
        try {
            const res = await axios.delete(`/api/worker-comment/${cid}`, { headers });
            alert.success(res?.data?.message || 'Comment deleted successfully!');
            handleEditTask(selectedTaskId);
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.error === 'Unauthorized') {
                alert.error('You can only delete your own comment.');
            } else if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to delete comment. Please try again.');
            }
        }
    };

    const handleEditComment = async (cid, updatedComment) => {
        const data = {
            comment: updatedComment,
        };
        try {
            const res = await axios.put(`/api/tasks/${selectedTaskId}/comments/${cid}`, data, { headers });
            alert.success(res?.data?.message || "Comment updated successfully");
            handleEditTask(selectedTaskId);
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to update comment. Please try again.');
            }
        }
    };

    const taskStatusColor = (status) => {
        const statusLower = status?.toLowerCase() || '';
        
        if (statusLower.includes('complete')) {
            return { backgroundColor: '#28a745' };
        } else if (statusLower.includes('progress')) {
            return { backgroundColor: '#ffc107' };
        } else if (statusLower.includes('pending')) {
            return { backgroundColor: '#6c757d' };
        } else {
            return { backgroundColor: '#6c757d' };
        }
    };

    // Add a handler for row click
    const handleRowClick = (task) => {
        setDetailTask(task);
        setDetailStatus(task.status);
        setIsDetailModal(true);
    };

    // Handle status update in detail modal
    const handleUpdateTaskStatus = async () => {
        if (!detailTask || !detailStatus) {
            alert.error('Please select a status');
            return;
        }

        try {
            // Use the same API structure as admin but with worker token
            const data = {
                phase_id: detailTask.phase_id,
                task_name: detailTask.task_name,
                status: detailStatus,
                priority: detailTask.priority,
                due_date: detailTask.due_date,
                description: detailTask.description,
                // Keep existing worker_ids and user_ids
                worker_ids: detailTask.workers ? detailTask.workers.map(w => w.id) : [],
                user_ids: detailTask.users ? detailTask.users.map(u => u.id) : []
            };

            const response = await axios.put(`/api/tasks/${detailTask.id}`, data, { headers });
            
            alert.success(response?.data?.message || 'Task status updated successfully!');
            setIsDetailModal(false);
            getTasks(); // Refresh the task list
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to update task status. Please try again.');
            }
        }
    };

    // When closing the comment modal, reload the DataTable to update comment count
    const handleCloseCommentModal = () => {
        console.log('Closing comment modal'); // Debug log
        setIsComModal(false);
        if (tableRef.current && $(tableRef.current).DataTable()) {
            $(tableRef.current).DataTable().ajax.reload();
        }
    };

    // Clear error message
    const clearError = () => {
        setError(null);
    };

    // Clear error when filters change
    useEffect(() => {
        if (error) {
            clearError();
        }
    }, [statusFilter, dateRange, search]);

    // Debug modal state changes
    useEffect(() => {
        console.log('Comment modal state changed:', isComModal); // Debug log
    }, [isComModal]);

    return (
        <div id="container">
            <Sidebar />
            <div id="content" style={{ overflowX: "hidden" }}>
                <div className="titleBox customer-title">
                    <div className="row align-items-center justify-space-between">
                        <div className="col">
                            <h1 className="page-title">Task Management</h1>
                        </div>
                    </div>
                </div>

                {/* Filters Section */}
                <div className="card mb-4 p-3" style={{ background: '#f8f9fa', border: '1px solid #e9ecef' }}>
                    {/* Top filter row */}
                    <div className="d-flex flex-wrap align-items-center mb-3">
                        <div className="mr-4 mb-2">
                            <div style={{ fontWeight: 'bold', marginBottom: 4 }}>Status</div>
                            <div className="d-flex flex-wrap">
                                <FilterButtons text="All" name="All" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                                <FilterButtons text="Pending" name="Pending" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                                <FilterButtons text="In Progress" name="In Progress" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                                <FilterButtons text="Complete" name="Complete" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                            </div>
                        </div>
                        <div className="mr-4 mb-2">
                            <div style={{ fontWeight: 'bold', marginBottom: 4 }}>Date Period</div>
                            <div className="d-flex flex-wrap">
                                <FilterButtons text="Day" name="Day" className="px-3 mr-2 mb-2" selectedFilter={datePeriod} setselectedFilter={val => { setDatePeriod(val); setDateRange(getDateRangeForPeriod('Day')); }} />
                                <FilterButtons text="Week" name="Week" className="px-3 mr-2 mb-2" selectedFilter={datePeriod} setselectedFilter={val => { setDatePeriod(val); setDateRange(getDateRangeForPeriod('Week')); }} />
                                <FilterButtons text="Month" name="Month" className="px-3 mr-2 mb-2" selectedFilter={datePeriod} setselectedFilter={val => { setDatePeriod(val); setDateRange(getDateRangeForPeriod('Month')); }} />
                            </div>
                        </div>
                        <div className="mb-2">
                            <div style={{ fontWeight: 500, marginBottom: 4 }}>Date Range</div>
                            <div className="d-flex align-items-center flex-wrap">
                                <input type="date" className="form-control mr-2 mb-2" style={{ width: 130 }} value={dateRange.start} onChange={e => setDateRange({ ...dateRange, start: e.target.value })} />
                                <span className="mx-1 mb-2">-</span>
                                <input type="date" className="form-control mr-2 mb-2" style={{ width: 130 }} value={dateRange.end} onChange={e => setDateRange({ ...dateRange, end: e.target.value })} />
                                <button className="btn btn-dark ml-2 mb-2" style={{ minWidth: 70 }} onClick={() => { setStatusFilter('All'); setDatePeriod(''); setDateRange({ start: '', end: '' }); setSearch(''); }}>Reset</button>
                            </div>
                        </div>
                    </div>
                    {/* Second row: Search */}
                    <div className="row align-items-end">
                        <div className="col-md-6 col-12 mb-2">
                            <label style={{ fontWeight: 500 }}>Search</label>
                            <input
                                type="text"
                                className="form-control"
                                placeholder="Search by name or description"
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                </div>

                {/* Error Display */}
                {error && (
                    <div className="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        {error}
                        <button type="button" className="close" onClick={clearError}>
                            <span>&times;</span>
                        </button>
                    </div>
                )}

                {/* Tasks Table */}
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <div className="table-responsive">
                                <table ref={tableRef} className="table table-bordered">
                                    {/* DataTables will populate this table */}
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <CommentModal
                comment={comment}
                isComModal={isComModal}
                setIsComModal={handleCloseCommentModal}
                handleComment={handleComment}
                handleEditComment={handleEditComment}
                taskComments={taskComments}
                handleDeleteComment={handleDeleteComment}
                taskName={taskName}
                setComments={setComments}
                isEditable={isEditable}
                setIsEditable={setIsEditable}
                userType="worker"
            />
            {/* Task Detail Modal (view only) */}
            <TaskDetailModal
                isOpen={isDetailModal}
                setIsOpen={setIsDetailModal}
                isEditing={false}
                taskName={detailTask?.task_name || ''}
                setTaskName={() => {}}
                selectedOptions={[]}
                handleSelectChange={() => {}}
                selectedWorkers={[]}
                handleWorkerSelectChange={() => {}}
                priority={detailTask?.priority || ''}
                setPriority={() => {}}
                dueDate={detailTask?.due_date || ''}
                setDueDate={() => {}}
                status={detailStatus}
                setStatus={setDetailStatus}
                handleUpdateTask={handleUpdateTaskStatus}
                handleAddCard={() => {}}
                team={detailTask?.users || []}
                worker={detailTask?.workers || []}
                setDescription={() => {}}
                description={detailTask?.description || ''}
                setSelectedFrequency={() => {}}
                selectedFrequency={detailTask?.frequency_id || 1}
                setRepeatancy={() => {}}
                repeatancy={detailTask?.repeatancy || ''}
                setUntilDate={() => {}}
                untilDate={detailTask?.until_date || ''}
                modalType="detail"
            />
        </div>
    );
}

export default Tasks;
