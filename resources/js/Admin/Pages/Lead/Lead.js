import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import ReactPaginate from "react-paginate";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useNavigate } from "react-router-dom";
import { CSVLink } from "react-csv";
import Swal from "sweetalert2";

import Sidebar from "../../Layouts/Sidebar";
import { useTranslation } from "react-i18next";
import ChangeStatusModal from "../../Components/Modals/ChangeStatusModal";
import { leadStatusColor } from "../../../Utils/client.utils";

export default function Lead() {
    const [leads, setLeads] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [filter, setFilter] = useState("all");
    const [condition, setCondition] = useState("");
    const [loading, setLoading] = useState("Loading...");
    const [changeStatusModal, setChangeStatusModal] = useState({
        isOpen: false,
        id: 0,
    });
    const navigate = useNavigate();
    const { t } = useTranslation();
    const leadStatuses = [
        "pending lead",
        "potential lead",
        "irrelevant",
        "uninterested",
        "unanswered",
        "potential client",
        "pending client",
        "freeze client",
        "active client",
    ];
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getleads = () => {
        axios.get("/api/admin/leads", { headers }).then((response) => {
            if (response.data.leads.data.length > 0) {
                setLeads(response.data.leads.data);
                setPageCount(response.data.leads.last_page);
            } else {
                setLeads([]);
                setLoading("No lead found");
            }
        });
    };

    useEffect(() => {
        getleads();
    }, []);

    const filterLeads = (e) => {
        setFilter(e.target.value);
        setCondition("search");

        axios
            .get(`/api/admin/leads?q=${e.target.value}` + "&condition=search", {
                headers,
            })
            .then((response) => {
                if (response.data.leads.data.length > 0) {
                    setLeads(response.data.leads.data);
                    setPageCount(response.data.leads.last_page);
                    setLoading("Loading...");
                } else {
                    setLeads([]);
                    setPageCount(response.data.leads.last_page);
                    setLoading("No lead found");
                }
            });
    };

    const filterLeadsStat = (s) => {
        setFilter(s);
        setCondition("filter");

        const params = {
            q: s,
            condition: "filter",
        };

        axios.get(`/api/admin/leads`, { headers, params }).then((response) => {
            if (response.data.leads.data.length > 0) {
                setLeads(response.data.leads.data);
                setPageCount(response.data.leads.last_page);
                setLoading("Loading...");
            } else {
                setLeads([]);
                setPageCount(response.data.leads.last_page);
                setLoading("No lead found");
            }
        });
    };

    const booknun = (s) => {
        setFilter(s);
        axios
            .get(`/api/admin/leads?action=${s}`, { headers })
            .then((response) => {
                if (response.data.leads.data.length > 0) {
                    setLeads(response.data.leads.data);
                    setPageCount(response.data.leads.last_page);
                } else {
                    setLeads([]);
                    setPageCount(response.data.leads.last_page);
                    setLoading("No lead found");
                }
            });
    };

    const handlePageClick = async (data) => {
        let currentPage = data.selected + 1;
        let cn = "&q=";

        axios
            .get(
                "/api/admin/leads?page=" +
                    currentPage +
                    cn +
                    filter +
                    "&condition=" +
                    condition,
                { headers }
            )
            .then((response) => {
                if (response.data.leads.data.length > 0) {
                    setLeads(response.data.leads.data);
                    setPageCount(response.data.leads.last_page);
                } else {
                    setLeads([]);
                    setLoading("No lead found");
                }
            });
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Lead!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/leads/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Lead has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getleads();
                        }, 1000);
                    });
            }
        });
    };

    const handleNavigate = (e, id) => {
        e.preventDefault();
        navigate(`/admin/view-lead/${id}`);
    };

    const copy = [...leads];
    const [order, setOrder] = useState("ASC");
    const sortTable = (e, col) => {
        let n = e.target.nodeName;
        if (n != "SELECT") {
            if (n == "TH") {
                let q = e.target.querySelector("span");
                if (q.innerHTML === "↑") {
                    q.innerHTML = "↓";
                } else {
                    q.innerHTML = "↑";
                }
            } else {
                let q = e.target;
                if (q.innerHTML === "↑") {
                    q.innerHTML = "↓";
                } else {
                    q.innerHTML = "↑";
                }
            }
        }

        if (order == "ASC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? 1 : -1
            );
            setLeads(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setLeads(sortData);
            setOrder("ASC");
        }
    };
    const toggleChangeStatusModal = (clientId = 0) => {
        setChangeStatusModal((prev) => {
            return {
                isOpen: !prev.isOpen,
                id: clientId,
            };
        });
    };
    const updateData = () => {
        setTimeout(() => {
            getleads();
        }, 1000);
    };
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">
                                {" "}
                                {t("admin.sidebar.leads")}
                            </h1>
                        </div>

                        <div className="col-sm-6">
                            <div className="search-data">
                                <div className="action-dropdown dropdown mt-md-4 mr-2 d-lg-none">
                                    <button
                                        type="button"
                                        className="btn btn-default dropdown-toggle"
                                        data-toggle="dropdown"
                                    >
                                        <i className="fa fa-filter"></i>
                                    </button>
                                    <div className="dropdown-menu">
                                        <button
                                            className="dropdown-item"
                                            onClick={(e) => {
                                                setCondition("filter");
                                                setFilter("all");
                                                getleads();
                                            }}
                                        >
                                            {t("admin.leads.All")}
                                        </button>
                                        {leadStatuses.map((_status, _index) => {
                                            return (
                                                <button
                                                    className="dropdown-item"
                                                    onClick={(e) => {
                                                        filterLeadsStat(
                                                            _status
                                                        );
                                                    }}
                                                    key={_index}
                                                >
                                                    {_status}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                <input
                                    type="text"
                                    className="form-control"
                                    onChange={(e) => {
                                        filterLeads(e);
                                    }}
                                    placeholder="Search"
                                />
                                <Link
                                    to="/admin/leads/create"
                                    className="btn btn-pink add-btn"
                                >
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    <span className="d-lg-block d-none">
                                        {t("admin.leads.AddNew")}
                                    </span>
                                </Link>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e, e.target.value)}
                            >
                                <option value="">
                                    {t("admin.leads.Options.sortBy")}
                                </option>
                                <option value="id">
                                    {t("admin.leads.Options.ID")}
                                </option>
                                <option value="name">
                                    {t("admin.leads.Options.Name")}
                                </option>
                                <option value="email">
                                    {t("admin.leads.Options.Email")}
                                </option>
                                <option value="phone">
                                    {t("admin.leads.Options.Phone")}
                                </option>
                                <option value="status">
                                    {t("admin.leads.Options.Status")}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="row mb-2 d-none d-lg-block">
                    <div className="col-sm-12">
                        <FilterButtons
                            text={t("admin.leads.All")}
                            className="px-3 mr-1"
                            value={"all"}
                            onClick={() => {
                                setCondition("filter");
                                setFilter("all");
                                getleads();
                            }}
                            selectedFilter={filter}
                        />
                        {leadStatuses.map((_status, _index) => {
                            return (
                                <FilterButtons
                                    text={_status}
                                    className="px-3 mr-1"
                                    value={_status}
                                    onClick={() => {
                                        filterLeadsStat(_status);
                                    }}
                                    key={_index}
                                    selectedFilter={filter}
                                />
                            );
                        })}
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            {leads.length > 0 ? (
                                <Table className="table table-bordered">
                                    <Thead>
                                        <Tr style={{ cursor: "pointer" }}>
                                            <Th
                                                onClick={(e) => {
                                                    sortTable(e, "id");
                                                }}
                                            >
                                                {t("admin.leads.Options.ID")}

                                                <span className="arr">
                                                    {" "}
                                                    &darr;{" "}
                                                </span>
                                            </Th>
                                            <Th
                                                onClick={(e) => {
                                                    sortTable(e, "name");
                                                }}
                                            >
                                                {t("admin.leads.Options.Name")}
                                                <span className="arr">
                                                    {" "}
                                                    &darr;{" "}
                                                </span>
                                            </Th>
                                            <Th
                                                onClick={(e) => {
                                                    sortTable(e, "email");
                                                }}
                                            >
                                                {t("admin.leads.Options.Email")}
                                                <span className="arr">
                                                    {" "}
                                                    &darr;{" "}
                                                </span>
                                            </Th>
                                            <Th
                                                onClick={(e) => {
                                                    sortTable(e, "phone");
                                                }}
                                            >
                                                {t("admin.leads.Options.Phone")}
                                                <span className="arr">
                                                    {" "}
                                                    &darr;{" "}
                                                </span>
                                            </Th>
                                            <Th
                                                onClick={(e) => {
                                                    sortTable(e, "status");
                                                }}
                                            >
                                                {t(
                                                    "admin.leads.Options.Status"
                                                )}
                                                <span className="arr">
                                                    {" "}
                                                    &darr;{" "}
                                                </span>
                                            </Th>
                                            <Th>{t("admin.leads.Action")}</Th>
                                        </Tr>
                                    </Thead>
                                    <Tbody>
                                        {leads.map((item, index) => {
                                            const _statusColor =
                                                leadStatusColor(
                                                    item.lead_status
                                                        ? item.lead_status
                                                              .lead_status
                                                        : ""
                                                );

                                            const statusSpanStyle = {
                                                backgroundColor:
                                                    _statusColor.backgroundColor,
                                                color: "#fff",
                                            };

                                            return (
                                                <Tr
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    key={index}
                                                >
                                                    <Td
                                                        onClick={(e) =>
                                                            handleNavigate(
                                                                e,
                                                                item.id
                                                            )
                                                        }
                                                    >
                                                        {item.id}
                                                    </Td>
                                                    <Td>
                                                        <Link
                                                            to={`/admin/view-lead/${item.id}`}
                                                        >
                                                            {item.firstname}{" "}
                                                            {item.lastname}
                                                        </Link>
                                                    </Td>
                                                    <Td
                                                        onClick={(e) =>
                                                            handleNavigate(
                                                                e,
                                                                item.id
                                                            )
                                                        }
                                                    >
                                                        {item.email}
                                                    </Td>
                                                    <Td>{item.phone}</Td>
                                                    <Td>
                                                        {item.lead_status ? (
                                                            <span
                                                                className="badge"
                                                                style={
                                                                    statusSpanStyle
                                                                }
                                                            >
                                                                {
                                                                    item
                                                                        .lead_status
                                                                        .lead_status
                                                                }
                                                            </span>
                                                        ) : (
                                                            "-"
                                                        )}
                                                    </Td>
                                                    <Td>
                                                        <div className="action-dropdown dropdown">
                                                            <button
                                                                type="button"
                                                                className="btn btn-default dropdown-toggle"
                                                                data-toggle="dropdown"
                                                            >
                                                                <i className="fa fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <div className="dropdown-menu">
                                                                <Link
                                                                    to={`/admin/leads/${item.id}/edit`}
                                                                    className="dropdown-item"
                                                                >
                                                                    {t(
                                                                        "admin.leads.Edit"
                                                                    )}
                                                                </Link>
                                                                <Link
                                                                    to={`/admin/view-lead/${item.id}`}
                                                                    className="dropdown-item"
                                                                >
                                                                    {t(
                                                                        "admin.leads.view"
                                                                    )}
                                                                </Link>
                                                                <button
                                                                    className="dropdown-item"
                                                                    onClick={() =>
                                                                        toggleChangeStatusModal(
                                                                            item.id
                                                                        )
                                                                    }
                                                                >
                                                                    Change
                                                                    status
                                                                </button>
                                                                <button
                                                                    className="dropdown-item"
                                                                    onClick={() =>
                                                                        handleDelete(
                                                                            item.id
                                                                        )
                                                                    }
                                                                >
                                                                    {t(
                                                                        "admin.leads.Delete"
                                                                    )}
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </Td>
                                                </Tr>
                                            );
                                        })}
                                    </Tbody>
                                </Table>
                            ) : (
                                <p className="text-center mt-5">{loading}</p>
                            )}
                            {leads.length > 0 ? (
                                <ReactPaginate
                                    previousLabel={"Previous"}
                                    nextLabel={"Next"}
                                    breakLabel={"..."}
                                    pageCount={pageCount}
                                    marginPagesDisplayed={2}
                                    pageRangeDisplayed={3}
                                    onPageChange={handlePageClick}
                                    containerClassName={
                                        "pagination justify-content-end mt-3"
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
            {changeStatusModal.isOpen && (
                <ChangeStatusModal
                    handleChangeStatusModalClose={toggleChangeStatusModal}
                    isOpen={changeStatusModal.isOpen}
                    clientId={changeStatusModal.id}
                    getUpdatedData={updateData}
                />
            )}
        </div>
    );
}
const FilterButtons = ({ text, className, selectedFilter, onClick, value }) => (
    <button
        className={`btn border  rounded ${className}`}
        style={
            selectedFilter === value
                ? { background: "white" }
                : {
                      background: "#2c3f51",
                      color: "white",
                  }
        }
        onClick={() => {
            onClick?.();
        }}
    >
        {text}
    </button>
);
