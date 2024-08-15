import React, { useEffect, useState } from "react";
import Moment from "moment";
import ReactPaginate from "react-paginate";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import Sidebar from "../../Layouts/Sidebar";
import FilterButtons from "../../../Components/common/FilterButton";

export default function Notification() {
    const { t } = useTranslation();
    const [notices, setNotices] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [currentPage, setCurrentPage] = useState(0);
    const [notificationGrpTypeFilter, setNotificationGrpTypeFilter] =
        useState("all");

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const headNotice = () => {
        let _filters = {};

        if (notificationGrpTypeFilter) {
            _filters.group_type = notificationGrpTypeFilter;
        }

        axios
            .get("/api/admin/notice", {
                headers,
                params: {
                    page: currentPage,
                    all: 1,
                    ..._filters,
                },
            })
            .then((res) => {
                if (res.data.notice.data) {
                    setNotices(res.data.notice.data);
                    setPageCount(res.data.notice.last_page);
                } else {
                    setNotices([]);
                }
            });
    };

    const clearAll = (e) => {
        e.preventDefault();
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Notices!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .post(`/api/admin/clear-notices`, { all: 1 }, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "All notices has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            window.location.reload(1);
                        }, 1000);
                    });
            }
        });
    };

    useEffect(() => {
        headNotice();
    }, [notificationGrpTypeFilter]);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("admin.dashboard.pending.notifications")}</h1>
                        </div>
                        <div className="col-sm-6">
                            <button
                                onClick={(e) => clearAll(e)}
                                className="btn navyblue float-right addButton"
                            >
                                {t("modal.clear")} {t("admin.global.All")} 
                            </button>
                        </div>
                    </div>
                </div>

                <div className="notification-page">
                    <div className="payment-filter mb-3">
                        <div className="row mb-2">
                            <div className="col-sm-12 d-md-flex flex-wrap align-items-center">
                                <div
                                    className="mr-3"
                                    style={{ fontWeight: "bold" }}
                                >
                                    {t("global.status")} 
                                </div>
                                <FilterButtons
                                    text={t("admin.global.All")} 
                                    className="px-3 mr-1"
                                    selectedFilter={notificationGrpTypeFilter}
                                    setselectedFilter={
                                        setNotificationGrpTypeFilter
                                    }
                                />

                                <FilterButtons
                                    text={t("admin.leads.AddLead.Options.paymentStatus")}
                                    className="px-3 mr-1"
                                    selectedFilter={notificationGrpTypeFilter}
                                    setselectedFilter={
                                        setNotificationGrpTypeFilter
                                    }
                                />

                                <FilterButtons
                                    text={t("admin.leads.AddLead.Options.changes-and-cancellation")}
                                    className="px-3 mr-1"
                                    selectedFilter={notificationGrpTypeFilter}
                                    setselectedFilter={
                                        setNotificationGrpTypeFilter
                                    }
                                />

                                <FilterButtons
                                    text={t("admin.leads.AddLead.Options.lead-client")}
                                    className="px-3 mr-1"
                                    selectedFilter={notificationGrpTypeFilter}
                                    setselectedFilter={
                                        setNotificationGrpTypeFilter
                                    }
                                />

                                <FilterButtons
                                    text={t("admin.leads.AddLead.Options.reviews-of-clients")}
                                    className="px-3 mr-1"
                                    selectedFilter={notificationGrpTypeFilter}
                                    setselectedFilter={
                                        setNotificationGrpTypeFilter
                                    }
                                />

                                <FilterButtons
                                    text={t("admin.leads.AddLead.Options.problem-with-workers")}
                                    className="px-3 mr-1"
                                    selectedFilter={notificationGrpTypeFilter}
                                    setselectedFilter={
                                        setNotificationGrpTypeFilter
                                    }
                                />

                                <FilterButtons
                                    text={t("admin.leads.AddLead.Options.worker-forms")}
                                    className="px-3 mr-1"
                                    selectedFilter={notificationGrpTypeFilter}
                                    setselectedFilter={
                                        setNotificationGrpTypeFilter
                                    }
                                />
                            </div>
                        </div>
                    </div>
                    <div className="card">
                        <div className="card-body">
                            {notices.length > 0 ? (
                                notices &&
                                notices.map((n, i) => {
                                    return (
                                        <div className="agg-list" key={i}>
                                            <div className="icons">
                                                <i className="fas fa-check-circle"></i>
                                            </div>
                                            <div className="agg-text">
                                                <h6
                                                    dangerouslySetInnerHTML={{
                                                        __html: n.data,
                                                    }}
                                                />
                                                <p>
                                                    {Moment(
                                                        n.created_at
                                                    ).format(
                                                        "DD MMM Y, HH:MM A"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    );
                                })
                            ) : (
                                <div className="form-control text-center">
                                    {t("admin.leads.AddLead.Options.norecord")}
                                </div>
                            )}

                            {notices.length > 0 ? (
                                <ReactPaginate
                                    previousLabel={"Previous"}
                                    nextLabel={"Next"}
                                    breakLabel={"..."}
                                    pageCount={pageCount}
                                    marginPagesDisplayed={2}
                                    pageRangeDisplayed={3}
                                    onPageChange={(data) => {
                                        setCurrentPage(data.selected + 1);
                                    }}
                                    containerClassName={
                                        "pagination justify-content-end mt-3 flex-wrap"
                                    }
                                    pageClassName={"page-item"}
                                    pageLinkClassName={"page-link"}
                                    previousClassName={"page-item"}
                                    previousLinkClassName={"page-link"}
                                    nextClassName={"page-item"}
                                    nextLinkClassName={"page-link"}
                                    breakClassName={"page-item"}
                                    breakLinkClassName={"page-link"}
                                    activeClassName={"active"}
                                />
                            ) : (
                                <></>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
