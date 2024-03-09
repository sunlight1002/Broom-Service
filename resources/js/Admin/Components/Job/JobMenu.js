import { useRef, useState } from "react";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import JobModal from "./JobModal";

const jobActions = [
    {
        key: "edit",
        label: "Edit",
    },
    {
        key: "delete",
        label: "Delete",
    },
];

const JobMenu = (props) => {
    const {
        addresses,
        worker,
        AllServices,
        AllFreq,
        handleInputChange,
        formValues,
        handleTmpFormValues,
        tmpFormValue,
        handleSaveJobForm,
        handleRemoveFormFields,
    } = props;
    let isAdd = useRef(true);
    let indexRef = useRef();
    const [isOpen, setIsOpen] = useState(false);
    return (
        <div>
            <div className="text-right">
                <button
                    type="button"
                    onClick={() => {
                        if (!addresses.length) {
                            alert("Please add property address");
                            return;
                        }
                        handleTmpFormValues({
                            service: "",
                            name: "",
                            type: "fixed",
                            freq_name: "",
                            frequency: "",
                            fixed_price: "",
                            jobHours: "",
                            rateperhour: "",
                            other_title: "",
                            totalamount: "",
                            template: "",
                            cycle: "",
                            period: "",
                            address: "",
                        });
                        isAdd.current = true;
                        setIsOpen(true);
                    }}
                    className="btn btn-success"
                >
                    {" "}
                    + Add Job
                </button>
            </div>
            <div className="card">
                <div className="card-body">
                    <div className="boxPanel">
                        {formValues.length > 0 ? (
                            <Table className="table table-bordered">
                                <Thead>
                                    <Tr>
                                        <Th>Address</Th>
                                        <Th>Worker Name</Th>
                                        <Th>Worker Availability</Th>
                                        <Th>Service</Th>
                                        <Th>Type</Th>
                                        <Th>Job Hours</Th>
                                        <Th>Price</Th>
                                        <Th>Frequency</Th>
                                        <Th>Actions</Th>
                                    </Tr>
                                </Thead>
                                <Tbody>
                                    {formValues.length > 0 &&
                                        formValues.map((item, adIndex) => {
                                            return (
                                                <Tr>
                                                    <Td>
                                                        {
                                                            addresses[
                                                                item.address
                                                            ]?.geo_address
                                                        }
                                                    </Td>
                                                    <Td>{item.woker_name}</Td>
                                                    <Td>{item.shift}</Td>
                                                    <Td>{item.name}</Td>
                                                    <Td>{item.type}</Td>
                                                    <Td>{item.jobHours}</Td>
                                                    <Td>
                                                        {item.type === "hourly"
                                                            ? item.jobHours *
                                                              item.rateperhour
                                                            : item.fixed_price}
                                                    </Td>
                                                    <Td>{item.days}</Td>
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
                                                                {jobActions.map(
                                                                    (
                                                                        menu,
                                                                        i
                                                                    ) => {
                                                                        return (
                                                                            <button
                                                                                type="buttton"
                                                                                className="dropdown-item"
                                                                                key={
                                                                                    menu.key
                                                                                }
                                                                                onClick={(
                                                                                    e
                                                                                ) => {
                                                                                    e.preventDefault();
                                                                                    if (
                                                                                        menu.key ===
                                                                                        "edit"
                                                                                    ) {
                                                                                        indexRef.current =
                                                                                            adIndex;
                                                                                        isAdd.current = false;
                                                                                        handleTmpFormValues(
                                                                                            item
                                                                                        );
                                                                                        setIsOpen(
                                                                                            true
                                                                                        );
                                                                                    } else {
                                                                                        handleRemoveFormFields(
                                                                                            adIndex
                                                                                        );
                                                                                    }
                                                                                }}
                                                                            >
                                                                                {
                                                                                    menu.label
                                                                                }
                                                                            </button>
                                                                        );
                                                                    }
                                                                )}
                                                            </div>
                                                        </div>
                                                    </Td>
                                                </Tr>
                                            );
                                        })}
                                </Tbody>
                            </Table>
                        ) : (
                            <p className="text-center mt-5">
                                {"Jobs not found!"}
                            </p>
                        )}
                    </div>
                </div>
            </div>
            {isOpen && (
                <JobModal
                    setIsOpen={setIsOpen}
                    isOpen={isOpen}
                    addresses={addresses}
                    worker={worker}
                    AllServices={AllServices}
                    AllFreq={AllFreq}
                    handleInputChange={handleInputChange}
                    tmpFormValue={tmpFormValue}
                    handleSaveJobForm={handleSaveJobForm}
                    isAdd={isAdd.current}
                    index={indexRef.current}
                />
            )}
        </div>
    );
};
export default JobMenu;
