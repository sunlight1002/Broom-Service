import React, { useEffect, useState } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import Sidebar from "../../Layouts/Sidebar";

const WorkerLeave = () => {
    const { t } = useTranslation();
    const [leaves, setLeaves] = useState([]);
    const [loading, setLoading] = useState(true);
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [statusFilter, setStatusFilter] = useState('all');
    const [searchQuery, setSearchQuery] = useState('');

    useEffect(() => {
        setLoading(true);
        axios
            .get("/api/admin/sick-leaves/list", {
                headers: {
                    Accept: "application/json",
                    Authorization: `Bearer ${localStorage.getItem("admin-token")}`,
                },
                params: {
                    page: currentPage,
                    status: statusFilter,
                    search: searchQuery,
                }
            })
            .then((response) => {
                setLeaves(response.data.data); // Adjust according to your API response structure
                setTotalPages(response.data.meta.last_page);
                setLoading(false);
            })
            .catch(() => setLoading(false));
    }, [currentPage, statusFilter, searchQuery]);



    const handleActionClick = (id, status) => {
        Swal.fire({
            title: status === 'rejected' ? t("admin.leaves.confirmation") : t("admin.leaves.approveConfirm"),
            input: status === 'rejected' ? 'textarea' : undefined,
            inputPlaceholder: status === 'rejected' ? t("admin.leaves.rejectionReason") : undefined,
            showCancelButton: true,
            confirmButtonText: t("admin.leaves.confirm"),
            cancelButtonText: t("admin.leaves.cancel"),
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
        }).then((result) => {
            if (result.isConfirmed) {
                handleApproval(id, status, result.value);
            }
        });
    };

    const handleApproval = (id, status, rejectionReason = '') => {
        const data = {
            status: status,
            rejection_comment: status === 'rejected' ? rejectionReason : null,
        };

        axios.post(`/api/admin/sick-leaves/${id}/approve`, data, {
            headers: {
                Authorization: `Bearer ${localStorage.getItem('admin-token')}`,
            },
        })
            .then((response) => {
                Swal.fire("Status updated", "", "success");
                setLeaves(leaves.map(leave => leave.id === id ? response.data : leave));
            })
            .catch(() => Swal.fire("Can't update status", "", "error"));
    };

    const getStatusStyle = (status) => {
        switch (status) {
            case 'approved':
                return {
                    color: 'green',
                    fontWeight: 'bold'
                };
            case 'pending':
                return { color: 'orange', fontWeight: 'bold' };
            case 'rejected':
                return { color: 'red', fontWeight: 'bold' };
            default:
                return {};
        }
    };

    const handleSearch = (e) => {
        setSearchQuery(e.target.value);
        setCurrentPage(1); // Reset to first page when search query changes
    };

    const handleStatusChange = (e) => {
        setStatusFilter(e.target.value);
        setCurrentPage(1); // Reset to first page when filter changes
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };

    const renderPagination = () => (
        <div className="pagination-controls">
            <button
                disabled={currentPage === 1}
                onClick={() => handlePageChange(currentPage - 1)}
            >
                Previous
            </button>
            <span>Page {currentPage} of {totalPages}</span>
            <button
                disabled={currentPage === totalPages}
                onClick={() => handlePageChange(currentPage + 1)}
            >
                Next
            </button>
        </div>
    );

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-12">
                            <h1 className="page-title">Leave Request</h1>
                        </div>
                    </div>
                </div>
                <div className="dashBox p-4" style={{ backgroundColor: "inherit", border: "none" }}>
                    {loading ? (
                        <FullPageLoader visible={loading} />
                    ) : (
                        <>
                            <div className="row mb-3">
                                <div className="col-sm-6">
                                    <input 
                                        type="text" 
                                        className="form-control" 
                                        placeholder={t("global.search")} 
                                        value={searchQuery} 
                                        onChange={handleSearch} 
                                    />
                                </div>
                                <div className="col-sm-6">
                                    <select className="form-control" value={statusFilter} onChange={handleStatusChange}>
                                        <option value="all">{t("global.all")}</option>
                                        <option value="pending">{t("worker.statusPending")}</option>
                                        <option value="approved">{t("worker.statusApproved")}</option>
                                        <option value="rejected">{t("worker.statusRejected")}</option>
                                    </select>
                                </div>
                            </div>
                            <table className="display table table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th>{t("worker.workerName")}</th>
                                        <th>{t("global.startDate")}</th>
                                        <th>{t("worker.endDate")}</th>
                                        <th>{t("worker.doctorReport")}</th>
                                        <th>{t("worker.reason")}</th>
                                        <th>{t("worker.status")}</th>
                                        <th>{t("worker.action")}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {leaves.map((leave) => (
                                        <tr key={leave.id}>
                                            <td>{leave.worker_name}</td>
                                            <td>{leave.start_date}</td>
                                            <td>{leave.end_date}</td>
                                            <td>
                                                {leave.doctor_report_path ? (
                                                    <>
                                                        <a 
                                                            href={leave.doctor_report_path} 
                                                            target="_blank" 
                                                            rel="noopener noreferrer"
                                                            style={{ marginRight: '15px' }}
                                                            title="View"
                                                        >
                                                            <i className="fas fa-eye " style={{ fontSize: '18px' }}></i>
                                                        </a>
                                                        <a 
                                                            href={leave.doctor_report_path} 
                                                            download 
                                                            title="Download"
                                                            style={{ color: 'blue', textDecoration: 'underline' }}
                                                        >
                                                            <i className="fas fa-download" style={{ fontSize: '18px' }}></i>
                                                        </a>
                                                    </>
                                                ) : (
                                                    "Not available"
                                                )}
                                            </td>
                                            <td>{leave.reason_for_leave}</td>
                                            <td style={getStatusStyle(leave.status)}>
                                                {leave.status}
                                            </td>
                                            <td>
                                                <div className="action-dropdown dropdown">
                                                    <button
                                                        className="btn btn-default dropdown-toggle"
                                                        type="button"
                                                        id={`dropdownMenuButton-${leave.id}`}
                                                        data-toggle="dropdown"
                                                        aria-haspopup="true"
                                                        aria-expanded="false"
                                                    >
                                                        <i className="fa fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <div className="dropdown-menu" aria-labelledby={`dropdownMenuButton-${leave.id}`}>
                                                        <button
                                                            type="button"
                                                            className="dropdown-item"
                                                            onClick={() => handleActionClick(leave.id, "approved")}
                                                        >
                                                          {t("worker.approve")}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="dropdown-item"
                                                            onClick={() => handleActionClick(leave.id, "rejected")}
                                                        >
                                                           {t("worker.reject")}
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {renderPagination()}
                        </>
                    )}
                </div>
            </div>
        </div>
    );
};

export default WorkerLeave;