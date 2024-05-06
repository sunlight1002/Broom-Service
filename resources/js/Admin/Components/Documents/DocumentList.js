import Moment from "moment";
const arr = ["visa", "passport"];

const DocumentList = ({ documents, worker }) => {
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
                                            {a}
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
                                        {d.file}
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default DocumentList;
