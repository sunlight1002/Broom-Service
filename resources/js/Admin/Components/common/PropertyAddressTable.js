import { memo, useState, useEffect } from "react";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";

const PropertyAddressTable = memo(function PropertyAddressTable({ clientId }) {
    const [address, setAddress] = useState([]);
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const getPropertyAddress = () => {
        axios
            .get(`/api/admin/clients/${parseInt(clientId)}/edit`, {
                headers,
            })
            .then((res) => {
                const { client } = res.data;
                if (
                    client.property_addresses &&
                    client.property_addresses.length > 0
                ) {
                    setAddress(res.data.client.property_addresses);
                }
            });
    };
    useEffect(() => {
        getPropertyAddress();
    }, [clientId]);
    return (
        <div>
            <div className="card">
                <div className="card-body">
                    <div className="boxPanel">
                        {address.length > 0 ? (
                            <Table className="table table-bordered">
                                <Thead>
                                    <Tr>
                                        <Th>Address</Th>
                                        <Th>Zipcode</Th>
                                        <Th>Latitude</Th>
                                        <Th>Longitude</Th>
                                    </Tr>
                                </Thead>
                                <Tbody>
                                    {address &&
                                        address.map((item, index) => {
                                            return (
                                                <Tr key={index}>
                                                    <Td>
                                                        {item.geo_address
                                                            ? item.geo_address
                                                            : "NA"}{" "}
                                                    </Td>
                                                    <Td>
                                                        {item.zipcode
                                                            ? item.zipcode
                                                            : "NA"}
                                                    </Td>
                                                    <Td>
                                                        {item.latitude
                                                            ? item.latitude
                                                            : "NA"}
                                                    </Td>
                                                    <Td>
                                                        {item.longitude
                                                            ? item.longitude
                                                            : "NA"}
                                                    </Td>
                                                </Tr>
                                            );
                                        })}
                                </Tbody>
                            </Table>
                        ) : (
                            <p className="text-center mt-5">
                                {"Address not found!"}
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
});

export default PropertyAddressTable;
