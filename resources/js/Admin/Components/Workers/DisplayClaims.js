import React, { useState, useEffect } from "react";
import axios from "axios";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import { Table, Button, Spinner, Form, Card } from "react-bootstrap";
import FullPageLoader from "../../../Components/common/FullPageLoader";

const DisplayClaims = ({ worker }) => {
    const { t } = useTranslation();
    const [claims, setClaims] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [filter, setFilter] = useState("All");
    const [search, setSearch] = useState("");

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        fetchClaims();
    }, [worker]);

    const fetchClaims = async () => {
        if (!worker?.id) return;
        setIsLoading(true);
        try {
            const response = await axios.get(`/api/admin/claims/${worker.id}`, { headers });
            console.log(response.data);
            
            setClaims(response.data.claims);
        } catch (error) {
            console.error("Error fetching claims", error);
        }
        setIsLoading(false);
    };

    const filteredClaims = claims?.filter(claim => 
        (filter === "All" || claim?.status === filter) && 
        claim.claim.toLowerCase().includes(search.toLowerCase())
    );

    return (
        <Card className=" mb-4 ">
            <Card.Body>
                <div className="d-flex justify-content-between align-items-center mb-3">
                    <h4>{t("admin.global.claim")}</h4>
                    <Form.Control 
                        type="text" 
                        placeholder={t("worker.search")}
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        style={{ maxWidth: "250px" }}
                    />
                </div>
                <Table striped bordered hover responsive>
                    <thead>
                        <tr>
                            <th>#Id</th>
                            <th>{t("admin.global.claim")}</th>
                            <th>{t("global.date")}</th>
                            <th>{t("admin.global.status")}</th>
                            {/* <th>{t("global.action")}</th> */}
                        </tr>
                    </thead>
                    <tbody>
                        {isLoading ? (
                            <tr>
                                <td colSpan="5" className="text-center">
                                    <Spinner animation="border" />
                                </td>
                            </tr>
                        ) : (
                            filteredClaims?.length > 0 ? (
                                filteredClaims?.map((claim, index) => (
                                    <tr key={claim.id}>
                                        <td>{index + 1}</td>
                                        <td>{claim.claim}</td>
                                        <td>{Moment(claim.created_at).format("DD/MM/YYYY")}</td>
                                        <td>{claim.status || "Pending"}</td>
                                        {/* <td>
                                            <Button variant="info" size="sm" className="mr-2">
                                                {t("admin.global.view")}
                                            </Button>
                                            <Button variant="danger" size="sm">
                                                {t("admin.global.delete")}
                                            </Button>
                                        </td> */}
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="5" className="text-center">{t("admin.hearing.noRecord")}</td>
                                </tr>
                            )
                        )}
                    </tbody>
                </Table>
                <FullPageLoader visible={isLoading} />
            </Card.Body>
        </Card>
    );
};

export default DisplayClaims;
