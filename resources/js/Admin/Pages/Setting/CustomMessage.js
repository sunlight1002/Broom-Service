import React, { useState, useEffect } from 'react'
import Sidebar from '../../Layouts/Sidebar';
import { useTranslation } from "react-i18next";
import FilterButtons from '../../../Components/common/FilterButton';
import useWindowWidth from '../../../Hooks/useWindowWidth';
import { useAlert } from "react-alert";
import Select from "react-select";
import { setSeconds } from 'date-fns';

const CustomMessage = () => {
    const { t, i18n } = useTranslation();
    const [type, setType] = useState("")
    const [filter, setFilter] = useState("All");
    const [status, setStatus] = useState("All");
    const [show, setShow] = useState(false)
    const [allWorkers, setAllWorkers] = useState([]);
    const [allWorkerLeads, setAllWorkerLeads] = useState([]);
    const [allClients, setAllClients] = useState([])
    const [workersInclude, setWorkersInclude] = useState([]);
    const [workerLeadInclude, setWorkerLeadInclude] = useState([]);
    const [workersExclude, setWorkersExclude] = useState([]);
    const [workerLeadExclude, setWorkerLeadExclude] = useState([]);
    const [clientsInclude, setClientsInclude] = useState([])
    const [clientsExclude, setClientsExclude] = useState([])
    const [dateRange, setDateRange] = useState({
        start_date: "",
        end_date: "",
    });
    const windowWidth = useWindowWidth()
    const [templates, setTemplates] = useState({
        message_heb: "",
        message_en: "",
        message_spa: "",
        message_ru: "",
    });

    const alert = useAlert();

    useEffect(() => {
        if (windowWidth < 768) {
            setShow(true)
        } else {
            setShow(false)
        }
    }, [windowWidth])


    const leadStatuses = {
        "pending": t("admin.leads.Pending"),
        "potential": t("admin.leads.Potential"),
        "irrelevant": t("admin.leads.Irrelevant"),
        "uninterested": t("admin.leads.Uninterested"),
        "unanswered": t("admin.leads.Unanswered"),
        "potential client": t("admin.leads.Potential_client"),
        "reschedule call": t("admin.leads.Reschedule_call"),
    };

    const clientStatuses = [
        t("admin.client.Potential"),
        t("admin.client.Pending_client"),
        t("admin.client.Active_client"),
        t("admin.client.Freeze_client"),
        t("admin.client.Past_client"),
    ];
    const workerStatuses = [
        t("admin.global.active"),
        t("admin.global.inactive"),
    ];

    const workerLeadStatuses = {
        irrelevant: t("admin.leads.Irrelevant"),
        "will-think": t("admin.leads.Will_think"),
        unanswered: t("admin.leads.Unanswered"),
        hiring: t("admin.leads.Hiring"),
        "not-hired": t("admin.leads.Not_hired"),
    };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        // if(templates.message_heb === "" || templates.message_en === "" || templates.message_spa === "" || templates.message_ru === "") {
        //     return alert.error("Please fill the fields");
        // }

        const selectedWorkerInculdeIds = workersInclude.map(worker => worker.value);
        const selectedWorkerExculdeIds = workersExclude.map(worker => worker.value);
        const selectedClientsInculdeIds = clientsInclude.map(client => client.value);
        const selectedClientsExculdeIds = clientsExclude.map(client => client.value);


        const data = {
            type, // 'leads', 'clients', or 'workers'
            status: status !== "Past client" ? status.toLowerCase() : "past",
            worker_inculde_ids: selectedWorkerInculdeIds, // Include selected workers
            worker_exclude_ids: selectedWorkerExculdeIds,
            client_include_ids: selectedClientsInculdeIds,
            client_exclude_ids: selectedClientsExculdeIds,
            templates
        };

        try {
            const response = await axios.post(`/api/admin/custom-message-send`, data, { headers });
            if (response.status === 200) {
                console.log(response.data.data);
                reset()

                Swal.fire({
                    icon: 'success',
                    title: t("global.success"),
                    text: t("swal.message_send"),
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: "error",
                text: "something went wrong",
            });
        }
    };


    const getWorkers = () => {
        axios.get("/api/admin/all-workers", { headers }).then((res) => {
            const { workers } = res.data;
            const mapWorkersArr = workers.map((w) => {
                let obj = {
                    value: w.id,
                    label: `${w.firstname} ${w.lastname}`,
                };
                return obj;
            });
            setAllWorkers(mapWorkersArr);
        });
    };

    const getWorkerLeads = () => {
        axios.get("/api/admin/worker-leads/get-all", { headers }).then((res) => {
            const { workerLeads } = res.data;
            const mapWorkersArr = workerLeads.map((w) => {
                let obj = {
                    value: w.id,
                    label: `${w.firstname} ${w.lastname}`,
                };
                return obj;
            });
            setAllWorkerLeads(mapWorkersArr);
        });
    };


    const getClients = () => {
        axios.get("/api/admin/all-clients", { headers }).then((res) => {
            const { clients } = res.data;
            const mapClientsArr = clients.map((c) => {
                let obj = {
                    value: c.id,
                    label: `${c.firstname} ${c.lastname}`,
                };
                return obj;
            });
            setAllClients(mapClientsArr);
        });
    };

    useEffect(() => {
        getWorkers();
        getWorkerLeads();
        getClients();
    }, []);

    const handleChange = (language) => (event) => {
        setTemplates({
            ...templates,
            [language]: event.target.value,
        });
    };

    const reset = () => [
        setTemplates({
            message_heb: "",
            message_en: "",
            message_spa: "",
            message_ru: "",
        }),
        setWorkersInclude([]),
        setWorkersExclude([]),
        setClientsInclude([]),
        setClientsExclude([]),
        setType("All"),
        setStatus("All"),
    ]


    return (
        <div id="container">
            <Sidebar />
            <div id="content" className="facebook">
                <div className=" m-0">
                    <div className="titleBox customer-title d-flex justify-content-between flex-column">
                        <div className="d-flex justify-content-between align-items-center mb-4">
                            <h1 className="page-title navyblueColor">{t("admin.sidebar.custom_message")}</h1>
                        </div>
                        {/* <div className="d-flex align-items-center flex-wrap">
                            <div
                                className="mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                {t("global.date_range")}
                            </div>

                            <div className="d-flex align-items-center flex-wrap">
                                <input
                                    className="form-control my-1"
                                    type="date"
                                    placeholder="From date"
                                    name="from filter"
                                    style={{ width: "fit-content" }}
                                    // value={dateRange.start_date}
                                    // onChange={(e) => {
                                    //     setDateRange({
                                    //         start_date: e.target.value,
                                    //         end_date: dateRange.end_date,
                                    //     });
                                    // }}
                                />
                                <div className="mx-2">{t("global.to")}</div>
                                <input
                                    className="form-control my-1"
                                    type="date"
                                    placeholder="To date"
                                    name="to filter"
                                    style={{ width: "fit-content" }}
                                    // value={dateRange.end_date}
                                    // onChange={(e) => {
                                    //     setDateRange({
                                    //         start_date: dateRange.start_date,
                                    //         end_date: e.target.value,
                                    //     });
                                    // }}
                                />
                            </div>
                        </div> */}



                        <div className="d-flex align-items-center flex-wrap">
                            <div
                                className="mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                {t("global.Type")}
                            </div>
                            <div className="d-flex align-items-center flex-wrap">
                                <Filter_Buttons
                                    text={t("admin.global.leads")}
                                    className="px-3 mr-1"
                                    value="leads"
                                    onClick={() => {
                                        setType("leads");
                                    }}
                                    selectedFilter={type}
                                />
                                <Filter_Buttons
                                    text={t("admin.global.clients")}
                                    className="px-3 mr-1"
                                    value="clients"
                                    onClick={() => {
                                        setType("clients");
                                    }}
                                    selectedFilter={type}
                                />
                                <Filter_Buttons
                                    text={t("admin.global.Workers")}
                                    className="px-3 mr-1"
                                    value="workers"
                                    onClick={() => {
                                        setType("workers");
                                    }}
                                    selectedFilter={type}
                                />
                                <Filter_Buttons
                                    text={t("admin.sidebar.worker_lead")}
                                    className="px-3 mr-1"
                                    value="worker_leads"
                                    onClick={() => {
                                        setType("worker_leads");
                                    }}
                                    selectedFilter={type}
                                />
                            </div>
                        </div>

                        {
                            type != "" && (
                                <div className="col-sm-6 mt-2 pl-0 d-flex">
                                    <div className="search-data">
                                        <div className="action-dropdown dropdown d-flex align-items-center mt-md-4 mr-2 d-lg-none">
                                            <div
                                                className=" mr-3"
                                                style={{ fontWeight: "bold" }}
                                            >
                                                {t("admin.global.status")}
                                            </div>
                                            <button
                                                type="button"
                                                className="btn btn-default navyblue dropdown-toggle"
                                                data-toggle="dropdown"
                                            >
                                                <i className="fa fa-filter"></i>
                                            </button>
                                            <span className="ml-2" style={{
                                                padding: "6px",
                                                border: "1px solid #ccc",
                                                borderRadius: "5px"
                                            }}>{status}</span>

                                            <div className="dropdown-menu dropdown-menu-right">
                                                {
                                                    type == "leads" && (
                                                        <>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("pending");
                                                                }}
                                                            >
                                                                {t("admin.leads.Pending")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("potential");
                                                                }}
                                                            >
                                                                {t("admin.leads.Potential")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("irrelevant");
                                                                }}
                                                            >
                                                                {t("admin.leads.Irrelevant")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("uninterested");
                                                                }}
                                                            >
                                                                {t("admin.leads.Uninterested")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("unanswered");
                                                                }}
                                                            >
                                                                {t("admin.leads.Unanswered")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("potential client");
                                                                }}
                                                            >
                                                                {t("admin.leads.Potential_client")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("reschedule call");
                                                                }}
                                                            >
                                                                {t("admin.leads.Reschedule_call")}
                                                            </button>
                                                        </>
                                                    )
                                                }

                                                {
                                                    type == "workers" && (
                                                        <>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("active");
                                                                }}
                                                            >
                                                                {t("admin.global.active")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("inactive");
                                                                }}
                                                            >
                                                                {t("admin.global.inactive")}
                                                            </button>
                                                        </>
                                                    )
                                                }

                                                {
                                                    type == "worker_leads" && (
                                                        <>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("irrelevant");
                                                                }}
                                                            >
                                                                {t("admin.leads.Irrelevant")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("will-think");
                                                                }}
                                                            >
                                                                {t("admin.leads.Will_think")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("unanswered");
                                                                }}
                                                            >
                                                                {t("admin.leads.Unanswered")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("hiring");
                                                                }}
                                                            >
                                                                {t("admin.leads.Hiring")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("not-hired");
                                                                }}
                                                            >
                                                                {t("admin.leads.Not_hired")}
                                                            </button>
                                                        </>
                                                    )
                                                }

                                                {
                                                    type == "clients" && (
                                                        <>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("potentail");
                                                                }}
                                                            >
                                                                {t("admin.client.Potential")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("pending client");
                                                                }}
                                                            >
                                                                {t("admin.client.Pending_client")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("active client");
                                                                }}
                                                            >
                                                                {t("admin.client.Active_client")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("freeze client");
                                                                }}
                                                            >
                                                                {t("admin.client.Freeze_client")}
                                                            </button>
                                                            <button
                                                                className="dropdown-item"
                                                                onClick={() => {
                                                                    setStatus("past client");
                                                                }}
                                                            >
                                                                {t("admin.client.Past_client")}
                                                            </button>
                                                        </>
                                                    )
                                                }
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )
                        }

                        {
                            type === "leads" && (
                                <div className=" mb-2  mt-2 d-none d-lg-flex align-items-center">
                                    <div
                                        className=" mr-3"
                                        style={{ fontWeight: "bold" }}
                                    >
                                        {t("admin.global.status")}
                                    </div>
                                    <div className="d-flex align-items-center flex-wrap">
                                        <FilterButtons
                                            text={t("admin.leads.All")}
                                            className="px-3 mr-1"
                                            selectedFilter={status}
                                            setselectedFilter={setStatus}
                                        />
                                        {Object.entries(leadStatuses).map(([key, value]) => {
                                            return (
                                                <FilterButtons
                                                    text={value}
                                                    name={key}
                                                    className="px-3 mr-1"
                                                    key={key}
                                                    selectedFilter={status}
                                                    setselectedFilter={setStatus}
                                                />
                                            );
                                        })}
                                    </div>
                                </div>
                            )
                        }

                        {
                            type === "worker_leads" && (
                                <div className=" mb-2  mt-2 d-none d-lg-flex align-items-center">
                                    <div
                                        className=" mr-3"
                                        style={{ fontWeight: "bold" }}
                                    >
                                        {t("admin.global.status")}
                                    </div>
                                    <div className="d-flex align-items-center flex-wrap">
                                        <FilterButtons
                                            text={t("admin.leads.All")}
                                            className="px-3 mr-1"
                                            selectedFilter={status}
                                            setselectedFilter={setStatus}
                                        />
                                        {Object.entries(workerLeadStatuses).map(([key, value]) => {
                                            return (
                                                <FilterButtons
                                                    text={value}
                                                    name={key}
                                                    className="px-3 mr-1"
                                                    key={key}
                                                    selectedFilter={status}
                                                    setselectedFilter={setStatus}
                                                />
                                            );
                                        })}
                                    </div>
                                </div>
                            )
                        }

                        {
                            type === "clients" && (
                                <div className=" mb-2 d-none d-lg-flex align-items-center mt-2">
                                    <div
                                        className=" mr-3"
                                        style={{ fontWeight: "bold" }}
                                    >
                                        {t("admin.global.status")}
                                    </div>
                                    <div className="d-flex align-items-center flex-wrap">
                                        <FilterButtons
                                            text={t("admin.leads.All")}
                                            className="px-3 mr-1"
                                            selectedFilter={status}
                                            setselectedFilter={setStatus}
                                        />
                                        {clientStatuses.map((_status, _index) => {
                                            return (
                                                <FilterButtons
                                                    text={_status}
                                                    className="px-3 mr-1"
                                                    key={_index}
                                                    selectedFilter={status}
                                                    setselectedFilter={setStatus}
                                                />
                                            );
                                        })}
                                    </div>
                                </div>
                            )
                        }

                        {
                            (type == "leads" || type == "clients") && (
                                <div className='row my-2'>
                                    <div className="col-sm-4 d-flex align-items-center">
                                        <div
                                            className=" mr-3"
                                            style={{ fontWeight: "bold" }}
                                        >
                                            {t("admin.global.client_includes")}
                                        </div>
                                        <div className="d-flex align-items-center flex-wrap">
                                            <Select
                                                value={clientsInclude}
                                                name="clients"
                                                isMulti
                                                options={allClients}
                                                className="basic-multi-single skyBorder"
                                                isClearable={true}
                                                placeholder={t(
                                                    "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                                )}
                                                classNamePrefix="select"
                                                onChange={(newValue) =>
                                                    setClientsInclude(newValue)
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div className="col-sm-4 d-flex align-items-center">
                                        <div
                                            className=" mr-3"
                                            style={{ fontWeight: "bold" }}
                                        >
                                            {t("admin.global.client_excludes")}
                                        </div>
                                        <div className="d-flex align-items-center flex-wrap">
                                            <Select
                                                value={clientsExclude}
                                                name="clients"
                                                isMulti
                                                options={allClients}
                                                className="basic-multi-single skyBorder"
                                                isClearable={true}
                                                placeholder={t(
                                                    "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                                )}
                                                classNamePrefix="select"
                                                onChange={(newValue) =>
                                                    setClientsExclude(newValue)
                                                }
                                            />
                                        </div>
                                    </div>
                                </div>
                            )
                        }

                        {
                            type === "workers" && (
                                <>
                                    <div className=" mb-2 d-none d-lg-flex align-items-center mt-2">
                                        <div
                                            className=" mr-3"
                                            style={{ fontWeight: "bold" }}
                                        >
                                            {t("admin.global.status")}
                                        </div>
                                        <div className="d-flex align-items-center flex-wrap">
                                            <FilterButtons
                                                text={t("admin.leads.All")}
                                                className="px-3 mr-1"
                                                selectedFilter={status}
                                                setselectedFilter={setStatus}
                                            />
                                            {workerStatuses.map((_status, _index) => {
                                                return (
                                                    <FilterButtons
                                                        text={_status}
                                                        className="px-3 mr-1"
                                                        key={_index}
                                                        selectedFilter={status}
                                                        setselectedFilter={setStatus}
                                                    />
                                                );
                                            })}
                                        </div>
                                    </div>
                                    <div className='row my-2'>
                                        <div className="col-sm-4 d-flex align-items-center">
                                            <div
                                                className=" mr-3"
                                                style={{ fontWeight: "bold" }}
                                            >
                                                {t("admin.global.worker_includes")}
                                            </div>
                                            <div className="d-flex align-items-center flex-wrap">
                                                <Select
                                                    value={workersInclude}
                                                    name="workers"
                                                    isMulti
                                                    options={allWorkers}
                                                    className="basic-multi-single skyBorder"
                                                    isClearable={true}
                                                    placeholder={t(
                                                        "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                                    )}
                                                    classNamePrefix="select"
                                                    onChange={(newValue) =>
                                                        setWorkersInclude(newValue)
                                                    }
                                                />
                                            </div>
                                            {/* </div> */}
                                        </div>
                                        <div className='col-sm-4 d-flex align-items-center'>
                                            <div
                                                className=" mr-3"
                                                style={{ fontWeight: "bold" }}
                                            >
                                                {t("admin.global.worker_excludes")}
                                            </div>
                                            <div className="d-flex align-items-center flex-wrap">
                                                <Select
                                                    value={workersExclude}
                                                    name="workers"
                                                    isMulti
                                                    options={allWorkers}
                                                    className="basic-multi-single skyBorder"
                                                    isClearable={true}
                                                    placeholder={t(
                                                        "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                                    )}
                                                    classNamePrefix="select"
                                                    onChange={(newValue) =>
                                                        setWorkersExclude(newValue)
                                                    }
                                                />
                                            </div>
                                            {/* </div> */}
                                        </div>
                                    </div>
                                </>
                            )
                        }


                        {/* {
                            type === "worker_leads" && (
                                <div className='row my-2'>
                                    <div className="col-sm-4 d-flex align-items-center">
                                        <div
                                            className=" mr-3"
                                            style={{ fontWeight: "bold" }}
                                        >
                                            {t("admin.global.worker_includes")}
                                        </div>
                                        <div className="d-flex align-items-center flex-wrap">
                                            <Select
                                                value={workerLeadInclude}
                                                name="workers"
                                                isMulti
                                                options={allWorkerLeads}
                                                className="basic-multi-single skyBorder"
                                                isClearable={true}
                                                placeholder={t(
                                                    "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                                )}
                                                classNamePrefix="select"
                                                onChange={(newValue) =>
                                                    setWorkerLeadInclude(newValue)
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div className='col-sm-4 d-flex align-items-center'>
                                        <div
                                            className=" mr-3"
                                            style={{ fontWeight: "bold" }}
                                        >
                                            {t("admin.global.worker_excludes")}
                                        </div>
                                        <div className="d-flex align-items-center flex-wrap">
                                            <Select
                                                value={workerLeadExclude}
                                                name="workers"
                                                isMulti
                                                options={allWorkerLeads}
                                                className="basic-multi-single skyBorder"
                                                isClearable={true}
                                                placeholder={t(
                                                    "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                                )}
                                                classNamePrefix="select"
                                                onChange={(newValue) =>
                                                    setWorkerLeadExclude(newValue)
                                                }
                                            />
                                        </div>
                                    </div>
                                </div>
                            )
                        } */}


                    </div>

                    <div className="dashBox" style={{ backgroundColor: "inherit", border: "none" }}>
                        <form onSubmit={handleSubmit} className={`d-flex ${show ? 'flex-wrap-reverse' : 'nowrap'}`}>
                            <div className="mt-3 mr-3 flex-grow-1 me-4 w-100">
                                <div className="form-group">
                                    <label htmlFor="hebrew">Hebrew</label>
                                    <textarea
                                        id="message_heb"
                                        className="form-control"
                                        maxLength={1000}
                                        rows="4"
                                        value={templates.message_heb}
                                        onChange={handleChange('message_heb')}
                                    />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="english">English</label>
                                    <textarea
                                        id="message_en"
                                        className="form-control"
                                        maxLength={1000}
                                        rows="4"
                                        value={templates.message_en}
                                        onChange={handleChange('message_en')}
                                    />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="spanish">Spanish</label>
                                    <textarea
                                        id="message_spa"
                                        className="form-control"
                                        maxLength={1000}
                                        rows="4"
                                        value={templates.message_spa}
                                        onChange={handleChange('message_spa')}
                                    />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="russian">Russian</label>
                                    <textarea
                                        id="message_ru"
                                        className="form-control"
                                        maxLength={1000}
                                        rows="4"
                                        value={templates.message_ru}
                                        onChange={handleChange('message_ru')}
                                    />
                                </div>
                                <div className='d-flex align-items-center justify-content-end'>
                                    <button type="button" onClick={() => reset()} className="mt-3 mx-2 btn btn-primary">{t("modal.reset")}</button>
                                    <button type="submit" className="mt-3 btn btn-primary">{t("global.send")}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}


const Filter_Buttons = ({ text, className, selectedFilter, onClick, value }) => (
    <button
        className={`btn border rounded ${className}`}
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

export default CustomMessage