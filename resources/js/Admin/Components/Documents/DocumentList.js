import Moment from "moment";
const arr = ["visa", "passport", "id_card"];

const DocumentList = ({ documents, worker, handleDelete }) => {
    const getExtension = (filename) => {
        return filename.split(".").pop();
    };

    return (
        <div>
            {arr.map(
                (a) =>
                    worker[a] && (
                        <div
                            key={a}
                            className="card card-widget widget-user-2"
                            style={{ boxShadow: "none" }}
                        >
                            <div className="card-comments cardforResponsive"></div>
                            <div
                                className="card-comment p-3"
                                style={{
                                    backgroundColor: "rgba(0,0,0,.05)",
                                    borderRadius: "5px",
                                }}
                            >
                                <div className="row">
                                    <div className="col-sm-3 col-3">
                                        <span
                                            className="noteDate"
                                            style={{ fontWeight: "600" }}
                                        >
                                            {Moment(worker.updated_at).format(
                                                "Y"
                                            )}
                                        </span>
                                    </div>
                                    <div className="col-sm-3 col-3">
                                        <p
                                            style={{
                                                fontSize: "16px",
                                                fontWeight: "600",
                                                textTransform: "capitalize",
                                            }}
                                        >
                                            {a === "id_card"? "id card" : a}
                                        </p>
                                    </div>
                                    <div className="col-sm-4 col-4">
                                        <span
                                            className="badge badge-warning text-dark"
                                            key={a}
                                        >
                                            <a
                                                href={`/storage/uploads/documents/${worker[a]}`}
                                                target={"_blank"}
                                            >
                                                {worker[a]}
                                            </a>
                                        </span>
                                    </div>
                                    {localStorage.getItem("admin-token") && (
                                        <div className="col-sm-2 col-2">
                                            <div className="float-right noteUser">
                                                <button
                                                    className="ml-2 btn bg-red"
                                                    onClick={(e) =>
                                                        handleDelete(e, a)
                                                    }
                                                >
                                                    <i className="fa fa-trash"></i>
                                                </button>
                                                &nbsp;
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    )
            )}
            {documents.map((d, i) => (
                <div
                    key={d.id}
                    className="card card-widget widget-user-2"
                    style={{ boxShadow: "none" }}
                >
                    <div className="card-comments cardforResponsive"></div>
                    <div
                        className="card-comment p-3"
                        style={{
                            backgroundColor: "rgba(0,0,0,.05)",
                            borderRadius: "5px",
                        }}
                    >
                        <div className="row">
                            <div className="col-sm-3 col-3">
                                <span
                                    className="noteDate"
                                    style={{ fontWeight: "600" }}
                                >
                                    {Moment(d.created_at).format("DD-MM-Y")}
                                </span>
                            </div>
                            <div className="col-sm-3 col-3">
                                <p
                                    style={{
                                        fontSize: "16px",
                                        fontWeight: "600",
                                    }}
                                >
                                    {d.document_type && d.document_type.name
                                        ? d.document_type.name
                                        : "NA"}
                                </p>
                            </div>
                            <div className="col-sm-4 col-4">
                                <span
                                    className="badge badge-warning text-dark"
                                    key={d.id}
                                >
                                    <a
                                        href={`/storage/uploads/documents/${d.file}`}
                                        target={"_blank"}
                                    >
                                        {d.name}.{getExtension(d.file)}
                                    </a>
                                </span>
                            </div>
                            {localStorage.getItem("admin-token") && (
                                <div className="col-sm-2 col-2">
                                    <div className="float-right noteUser">
                                        <button
                                            className="ml-2 btn bg-red"
                                            onClick={(e) =>
                                                handleDelete(e, d.id)
                                            }
                                        >
                                            <i className="fa fa-trash"></i>
                                        </button>
                                        &nbsp;
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default DocumentList;
