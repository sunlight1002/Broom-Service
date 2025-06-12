import React, { useEffect, useState } from "react";
import axios from "axios";
import { Link } from "react-router-dom";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import Button from "react-bootstrap/Button";
import Sidebar from "./Layouts/Sidebar";
import UserIcon from "../Assets/image/icons/user-client.jpeg";
import Jobs from "./Components/Dashboard/jobs";
import Pendings from "./Components/Dashboard/pendings";
import { useTranslation } from "react-i18next";
import { getCookie } from "./Components/common/Cookies";
import job from "../../../public/images/job.png";
import meeting from "../../../public/images/meeting.png";
import newClient from "../../../public/images/newClient.png";
import newWorker from "../../../public/images/newWorker.png";
import activeWorker from "../../../public/images/activeWorker.png";
import activeClient from "../../../public/images/activeClient.png";
import clientLeads from "../../../public/images/clientLeads.png";
import workerLeads from "../../../public/images/workerLeads.png";
import pendingOffer from "../../../public/images/pendingOffer.png";
import pendingContract from "../../../public/images/pendingContract.png";
import incomeIcon from "../../../public/images/income.png";
import outcome from "../../../public/images/outcome.png";
// import json from "./json.json";

export default function Dashboard() {
    const [totalJobs, setTotalJobs] = useState([0]);
    const [totalNewClients, setTotalNewClients] = useState([0]);
    const [totalNewWorkers, setTotalNewWorkers] = useState([0]);
    const [totalActiveClients, setTotalActiveClients] = useState([0]);
    const [totalLeads, setTotalLeads] = useState([0]);
    const [totalWorkers, setTotalWorkers] = useState([0]);
    const [totalWorkerLeads, setTotalWorkerLeads] = useState([0]);
    const [totalOffers, setTotalOffers] = useState([0]);
    const [totalSchedules, setTotalSchedules] = useState([0]);
    const [contracts, setContracts] = useState([0]);
    const [latestJobs, setlatestJobs] = useState([]);
    const [income, setIncome] = useState(0);
    const [expense, setExpense] = useState(0);
    const [loading, setLoading] = useState("Loading...");
    const [role, setRole] = useState("");
    const [dateRange, setDateRange] = useState({
        start_date: "",
        end_date: "",
    });
    const [searchQuery, setSearchQuery] = useState("");
    const [showDropdown, setShowDropdown] = useState(false);
    const [searchResults, setSearchResults] = useState({
        clients: [],
        workers: [],
        WorkerLeads: [],
    });
    const [selected, setSelected] = useState("today");
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const navigate = useNavigate();

    // optionally: debounce search for performance
    useEffect(() => {
        const timeout = setTimeout(() => {
            // call API or filter logic here
            console.log("Search:", searchQuery);
        }, 300);

        return () => clearTimeout(timeout);
    }, [searchQuery]);

    const fetchSearchResults = async (query) => {
        try {
            const response = await axios.get("/api/admin/search", { params: { q: query }, headers });
            console.log(response.data);

            setSearchResults(response.data);
        } catch (error) {
            console.error("Search error:", error);
            setSearchResults({
                clients: [],
                workers: [],
                WorkerLeads: [],
            });
        }
    };

    const hasResults =
        searchResults.clients.length ||
        searchResults.workers.length ||
        searchResults.WorkerLeads.length;
    console.log("HAsResult",hasResults)

    const handleInputChange = (e) => {
        const value = e.target.value;
        console.log(value);

        setSearchQuery(value);
        setShowDropdown(!!value);

        if (value.trim()) {
            fetchSearchResults(value);
        } else {
            setSearchResults({ clients: [], workers: [], leads: [] });
        }
    };

    const getCompletedJobs = (
        startDate = null,
        endDate = null
    ) => {
        const params = { selected };
        if (selected === "custom") {
            params.start_date = startDate;
            params.end_date = endDate;
        }

        axios
            .get("/api/admin/dashboard", { params, headers })
            .then((response) => {
                setTotalJobs(response.data.total_jobs);
                setTotalNewClients(response.data.total_new_clients);
                setTotalNewWorkers(response.data.total_new_workers);
                setTotalActiveClients(response.data.total_active_clients);
                setTotalLeads(response.data.total_leads);
                setTotalWorkers(response.data.total_workers);
                setTotalWorkerLeads(response.data.total_worker_leads);
                setTotalOffers(response.data.total_offers);
                setContracts(response.data.total_contracts);
                setTotalSchedules(response.data.total_schedules);
                setlatestJobs(
                    response.data.latest_jobs.data.length > 0
                        ? response.data.latest_jobs.data
                        : "No job found"
                );
            });
    };

    const getIncome = (start_date = null, end_date = null) => {
        axios
            .post("/api/admin/income", { start_date, end_date, selected }, { headers })
            .then((res) => {
                const profitArray = res.data?.graph?.data?.profit || [];
                const expenseArray = res.data?.graph?.data?.expense || [];

                const totalProfit = Array.isArray(profitArray)
                    ? profitArray.reduce((acc, val) => {
                        return acc + val;
                    }, 0)
                    : 0;


                const totalExpense = Array.isArray(expenseArray)
                    ? expenseArray.reduce((acc, val) => {
                        return acc + val;
                    }, 0)
                    : 0;

                setIncome(totalProfit);
                setExpense(totalExpense);
            })
            .catch((err) => {
                console.error("Error fetching income", err);
                setIncome(0); // fallback
            });
    };


    const getAdmin = () => {
        axios.get(`/api/admin/details`, { headers }).then((res) => {
            setRole(res.data.success.role);
        });
    };


    useEffect(() => {
        getCompletedJobs();
        // getUtilityTemplates();

        getIncome();
        // latestClients();
        getAdmin();
    }, [selected]);


    const handleSelect = (filter) => {
        setSelected(filter);

        let startDate = null;
        let endDate = null;

        if (filter === "custom") {
            startDate = dateRange.start_date;
            endDate = dateRange.end_date;
        } else {
            setDateRange({ start_date: "", end_date: "" });

            if (filter === "today") {
                startDate = endDate = new Date().toISOString().split("T")[0];
            } else if (filter === "this_week") {
                startDate = new Date();
                startDate.setDate(startDate.getDate() - startDate.getDay());
                startDate = startDate.toISOString().split("T")[0];

                endDate = new Date();
                endDate.setDate(endDate.getDate() + (6 - endDate.getDay()));
                endDate = endDate.toISOString().split("T")[0];
            } else if (filter === "this_month") {
                startDate = new Date();
                startDate.setDate(1);
                startDate = startDate.toISOString().split("T")[0];

                endDate = new Date(startDate);
                endDate.setMonth(endDate.getMonth() + 1);
                endDate.setDate(0);
                endDate = endDate.toISOString().split("T")[0];
            } else if (filter === "all_time") {
                startDate = null;
                endDate = null;
            }
        }

        getCompletedJobs(filter, startDate, endDate);

    };
    // useEffect(() => {
    //     const savedDateRange = localStorage.getItem("dateRange");
    //     if (savedDateRange) {
    //         setDateRange(JSON.parse(savedDateRange));
    //     }
    // }, []);

    useEffect(() => {
        if (dateRange.start_date && dateRange.end_date) {
            getCompletedJobs(
                dateRange.start_date,
                dateRange.end_date
            );
            getIncome(dateRange.start_date, dateRange.end_date);
        }
    }, [dateRange]);

    return (
        <div id="container column-left">
            <Sidebar />
            <div id="content">
                <div className="adminDash">
                    <div className="titleBox">
                        <h1 className="page-title">
                            {t("admin.sidebar.dashboard")}
                        </h1>
                    </div>
                    <div className="date-class d-flex justify-content-between align-items-center flex-wrap">
                        <div className="d-flex align-items-center flex-wrap my-2">
                            <div
                                style={{
                                    display: "flex",
                                    overflowX: "scroll",
                                    // marginBottom: "24px",
                                    justifyContent: "flex-start",
                                    alignItems: "center",
                                    scrollbarWidth: "none",
                                }}
                                className="hide-scrollbar flex-wrap"
                            >
                                <button
                                    type="button"
                                    className={`btn btn-default mx-1 my-1 daybtn ${selected === "today" ? "active" : ""
                                        }`}
                                    onClick={() => handleSelect("today")}
                                >
                                    {t("admin.sidebar.day.today")}
                                </button>
                                <button
                                    type="button"
                                    className={`btn btn-default mx-1 my-1 daybtn ${selected === "this_week" ? "active" : ""
                                        }`}
                                    onClick={() => handleSelect("this_week")}
                                >
                                    {t("admin.sidebar.day.this week")}
                                </button>
                                <button
                                    type="button"
                                    className={`btn btn-default mx-1 my-1 daybtn ${selected === "this_month" ? "active" : ""
                                        }`}
                                    onClick={() => handleSelect("this_month")}
                                >
                                    {t("admin.sidebar.day.this month")}
                                </button>
                                <button
                                    type="button"
                                    className={`btn btn-default mx-1 my-1 daybtn ${selected === "custom" ? "active" : ""
                                        }`}
                                    onClick={() => handleSelect("custom")}
                                >
                                    {t("admin.sidebar.day.custom")}
                                </button>
                                <button
                                    type="button"
                                    style={{ marginRight: "20px" }}
                                    className={`btn btn-default mx-1 my-1 daybtn ${selected === "all_time" ? "active" : ""
                                        }`}
                                    onClick={() => handleSelect("all_time")}
                                >
                                    {t("admin.sidebar.day.all time")}
                                </button>
                            </div>
                            <div
                                style={{
                                    display: "flex",
                                    // marginBottom: "24px",
                                    overflowX: "scroll",
                                    alignItems: "center",
                                    scrollbarWidth: "none",
                                }}
                                className="hide-scrollbar mx-2"
                            >
                                <p className="date">{t("admin.dashboard.datePeriod")}</p>
                                <div className="d-flex align-items-center">
                                    <input
                                        className="form-control calender"
                                        type="date"
                                        placeholder="From date"
                                        name="from filter"
                                        style={{ width: "fit-content" }}
                                        value={dateRange.start_date}
                                        onChange={(e) => {
                                            const updatedDateRange = {
                                                start_date: e.target.value,
                                                end_date: dateRange.end_date,
                                            };

                                            setDateRange(updatedDateRange);
                                            setSelected("custom");
                                            localStorage.setItem(
                                                "dateRange",
                                                JSON.stringify(updatedDateRange)
                                            );
                                        }}
                                    />
                                    <div className="mx-2">-</div>
                                    <input
                                        className="form-control calender"
                                        type="date"
                                        placeholder="To date"
                                        name="to_filter"
                                        style={{ width: "fit-content" }}
                                        value={dateRange.end_date}
                                        onChange={(e) => {
                                            const updatedDateRange = {
                                                start_date: dateRange.start_date,
                                                end_date: e.target.value,
                                            };

                                            setDateRange(updatedDateRange);
                                            setSelected("custom");
                                            // Corrected: JSON.stringify instead of json.stringify
                                            localStorage.setItem(
                                                "dateRange",
                                                JSON.stringify(updatedDateRange)
                                            );
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                        <div
                            style={{
                                display: "flex",
                                alignItems: "center",
                                scrollbarWidth: "none",
                            }}
                            className="hide-scrollbar mx-2"
                        >
                            <p className="date">{t("admin.dashboard.generalSearch")}</p>
                            <div className="position-relative" style={{ maxWidth: "300px" }}>
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Search..."
                                    value={searchQuery}
                                    onChange={handleInputChange}
                                    onFocus={() => searchQuery && setShowDropdown(true)}
                                    onBlur={() => setTimeout(() => setShowDropdown(false), 200)}
                                />

                                {showDropdown && (
                                    <div
                                        className="dropdown-menu show w-100 mt-1 shadow"
                                        style={{ maxHeight: "300px", overflowY: "auto" }}
                                    >
                                        {hasResults ? (
                                            Object.entries(searchResults).map(([key, items]) =>
                                                items.length > 0 ? (
                                                    <React.Fragment key={key}>
                                                        <h6 className="dropdown-header text-capitalize">{key}:</h6>
                                                        {items.map((item) => (
                                                           
                                                            <button
                                                                key={item.id}
                                                                className="dropdown-item text-truncate"
                                                                onClick={() => {
                                                                    if (item.type === "client" && item.status == "2") {
                                                                        navigate(`/admin/clients/view/${item.id}`);
                                                                    } else if (item.type === "client" && item.status != "2") {
                                                                        navigate(`/admin/leads/view/${item.id}`);
                                                                    } else if (item.type === "user") {
                                                                        navigate(`/admin/workers/view/${item.id}`);
                                                                    } else if (item.type === "workerlead") {
                                                                        navigate(`/admin/worker-leads/view/${item.id}`);
                                                                    }
                                                                }}
                                                            >
                                                                {item.firstname + " " + item.lastname}
                                                            </button>
                                                        ))}
                                                    </React.Fragment>
                                                ) : null
                                            )
                                        ) : (
                                            <div className="dropdown-item text-muted text-center">No results found</div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="row mt-3">
                        {role !== "hr" && (
                            <>
                                <div className="col-lg-3 col-sm-6 col-xs-6">
                                    <Link to="/admin/jobs">
                                        <div className="dashBox">
                                            <div className="dashIcon">
                                                <img src={job} alt="job"></img>
                                            </div>
                                            <div className="dashText">
                                                <h3>{totalJobs}</h3>
                                                <p>
                                                    {t(
                                                        "admin.dashboard.jobPosted"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                                <div className="col-lg-3  col-sm-6 col-xs-6">
                                    <Link to="/admin/schedule">
                                        <div className="dashBox">
                                            <div className="dashIcon">
                                                <img
                                                    src={meeting}
                                                    alt="meeting"
                                                ></img>
                                            </div>
                                            <div className="dashText">
                                                <h3>{totalSchedules}</h3>
                                                <p>
                                                    {" "}
                                                    {t(
                                                        "admin.dashboard.meetings"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                                <div className="col-lg-3 col-sm-6 col-xs-6">
                                    <Link to="/admin/clients?type=active%20client">
                                        <div className="dashBox">
                                            <div className="dashIcon">
                                                <img
                                                    src={activeClient}
                                                    alt="activeClient"
                                                ></img>
                                            </div>
                                            <div className="dashText">
                                                <h3>{totalActiveClients}</h3>
                                                <p>
                                                    {" "}
                                                    {t(
                                                        "admin.dashboard.activeClients"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </>
                        )}
                        <div className="col-lg-3 col-sm-6 col-xs-6">
                            {/* <Link to="/admin/workers"> */}
                            <Link to="/admin/workers?type=active%worker">
                                <div className="dashBox">
                                    <div className="dashIcon">
                                        <img
                                            src={activeWorker}
                                            alt="activeWorker"
                                        ></img>
                                    </div>
                                    <div className="dashText">
                                        <h3>{totalWorkers}</h3>
                                        <p>
                                            {" "}
                                            {t("admin.dashboard.activeWorkers")}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                        {role !== "hr" && (
                            <>
                                <div className="col-lg-3  col-sm-6 col-xs-6">
                                    <Link to="/admin/clients">
                                        <div className="dashBox">
                                            <div className="dashIcon">
                                                <img
                                                    src={newClient}
                                                    alt="newClient"
                                                ></img>
                                            </div>
                                            <div className="dashText">
                                                <h3>{totalNewClients}</h3>
                                                <p>
                                                    {" "}
                                                    {t(
                                                        "admin.dashboard.newClients"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                                <div className="col-lg-3  col-sm-6 col-xs-6">
                                    <Link to="/admin/workers">
                                        <div className="dashBox">
                                            <div className="dashIcon">
                                                <img
                                                    src={newWorker}
                                                    alt="newWorkder"
                                                ></img>
                                            </div>
                                            <div className="dashText">
                                                <h3>{totalNewWorkers}</h3>
                                                <p>
                                                    {" "}
                                                    {t(
                                                        "admin.dashboard.newWorker"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                                <div className="col-lg-3  col-sm-6 col-xs-6">
                                    <Link to="/admin/leads">
                                        <div className="dashBox">
                                            <div className="dashIcon">
                                                <img
                                                    src={clientLeads}
                                                    alt="clientLeads"
                                                ></img>
                                            </div>
                                            <div className="dashText">
                                                <h3>{totalLeads}</h3>
                                                <p>
                                                    {" "}
                                                    {t(
                                                        "admin.dashboard.clientLeads"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </>
                        )}
                        <div className="col-lg-3  col-sm-6 col-xs-6">
                            <Link to="/admin/worker-leads">
                                <div className="dashBox">
                                    <div className="dashIcon">
                                        <img
                                            src={workerLeads}
                                            alt="workerLeads"
                                        ></img>
                                    </div>
                                    <div className="dashText">
                                        <h3>{totalWorkerLeads}</h3>
                                        <p> {t("admin.sidebar.worker_lead")}</p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                        {role !== "hr" && (
                            <>
                                <div className="col-lg-3  col-sm-6 col-xs-6">
                                    <Link to="/admin/offered-price">
                                        <div className="dashBox">
                                            <div className="dashIcon">
                                                <img
                                                    src={pendingOffer}
                                                    alt="pendingoffer"
                                                ></img>
                                            </div>
                                            <div className="dashText">
                                                <h3>{totalOffers}</h3>
                                                <p>
                                                    {" "}
                                                    {t(
                                                        "admin.dashboard.pendingOfferedPrice"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                                <div className="col-lg-3  col-sm-6 col-xs-6">
                                    <Link to="/admin/contracts">
                                        <div className="dashBox">
                                            <div className="dashIcon">
                                                <img
                                                    src={pendingContract}
                                                    alt="pendingContract"
                                                ></img>
                                            </div>
                                            <div className="dashText">
                                                <h3>{contracts}</h3>
                                                <p>
                                                    {" "}
                                                    {t(
                                                        "admin.dashboard.pendingContract"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </>
                        )}

                        {role && role == "superadmin" && (
                            <>
                                <div className="col-lg-3  col-sm-6 col-xs-6">
                                    <Link to="/admin/income">
                                        <div className="dashBox">
                                            <div className="dashIcon">
                                                <img
                                                    src={incomeIcon}
                                                    alt="income"
                                                ></img>
                                            </div>
                                            <div className="dashText">
                                                <h3>{income.toFixed(2)} {t("global.currency")}</h3>
                                                <p>
                                                    {" "}
                                                    {t(
                                                        "admin.dashboard.income"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                                <div className="col-lg-3  col-sm-6 col-xs-6">
                                    <Link to="/admin/income">
                                        <div className="dashBox">
                                            <div className="dashIcon">
                                                <img
                                                    src={outcome}
                                                    alt="outcome"
                                                ></img>
                                            </div>
                                            <div className="dashText">
                                                <h3>{expense}</h3>
                                                <p>
                                                    {" "}
                                                    {t(
                                                        "admin.dashboard.outcome"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
